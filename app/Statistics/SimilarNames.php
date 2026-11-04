<?php

namespace App\Statistics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Finds MillType or WoodSpecies names that might be the same thing written differently, as candidates for
 * stakeholders to consider merging. It only reports; it never changes data.
 *
 * Two tiers:
 *  - groups: names that are very likely the same (same words once punctuation, case, plurals and "and" are set
 *    aside, or an abbreviation of the other);
 *  - related: pairs of those groups where every word of one appears in the other ("Chip" and "Chip Mill"), or
 *    that differ by a letter or two ("Pallet" and "Pellet"). These need a person to judge.
 *
 * Each name also lists the import mappers whose code mentions it (string literals only, so commented-out
 * mappings don't count). A name no mapper mentions came straight from a state's source data, so merging it
 * would also need a mapper entry, or the next import would bring it back.
 */
class SimilarNames
{
    /** Words that don't change what a name means. */
    private const array IGNORED_WORDS = ['and', 'n', 'the', 'of'];

    /**
     * @var array<int, array{id: int, name: string, mills: int, states: int, mappers: list<string>, words: list<string>, initials: string}>
     */
    private array $items;

    /**
     * @param  'mill_types'|'wood_species'  $table
     */
    public function __construct(string $table)
    {
        [$pivot, $key] = match ($table) {
            'mill_types' => ['mill_mill_type', 'mill_type_id'],
            'wood_species' => ['mill_wood_species', 'wood_species_id'],
        };

        $rows = DB::table($table)
            ->leftJoin($pivot, "{$pivot}.{$key}", '=', "{$table}.id")
            ->leftJoin('mills', 'mills.id', '=', "{$pivot}.mill_id")
            ->groupBy("{$table}.id", "{$table}.name")
            ->orderBy("{$table}.name")
            ->get([
                "{$table}.id",
                "{$table}.name",
                DB::raw('COUNT(DISTINCT mills.id) AS mills'),
                DB::raw('COUNT(DISTINCT mills.state_id) AS states'),
            ]);

        $mentions = self::mapperStrings();

        $this->items = [];
        foreach ($rows as $row) {
            $this->items[(int) $row->id] = [
                'id' => (int) $row->id,
                'name' => $row->name,
                'mills' => (int) $row->mills,
                'states' => (int) $row->states,
                'mappers' => $mentions[Str::lower(trim($row->name))] ?? [],
                'words' => self::words($row->name),
                'initials' => self::initials($row->name),
            ];
        }
    }

    public static function millTypes(): self
    {
        return new self('mill_types');
    }

    public static function woodSpecies(): self
    {
        return new self('wood_species');
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Groups of names that are very likely the same, largest mill count first within each group.
     *
     * @return list<array{reasons: list<string>, mills: int, names: list<array>}>
     */
    public function groups(): array
    {
        [$parent, $reasons] = $this->cluster();

        $groups = [];
        foreach ($this->items as $id => $item) {
            $groups[$this->find($parent, $id)][] = $item;
        }

        $result = [];
        foreach ($groups as $root => $members) {
            if (count($members) < 2) {
                continue;
            }
            usort($members, fn ($a, $b) => [$b['mills'], $a['name']] <=> [$a['mills'], $b['name']]);
            $result[] = [
                'reasons' => array_values(array_unique($reasons[$root] ?? [])),
                'mills' => array_sum(array_column($members, 'mills')),
                'names' => $members,
            ];
        }

        usort($result, fn ($a, $b) => $b['mills'] <=> $a['mills']);

        return $result;
    }

    /**
     * Pairs of names (or likely-same groups) that might be the same and need a person to judge:
     *  - "Contained": every word of the first appears in the second ("Chip" and "Chip Mill");
     *  - "Spelling": they differ by a letter or two, which is a typo or a different word ("Pallet" and "Pellet").
     *
     * @return list<array{first: list<array>, second: list<array>, reason: 'Contained'|'Spelling', mills: int}>
     */
    public function related(): array
    {
        [$parent] = $this->cluster();

        // one entry per likely-same group, with the words of all its names
        $clusters = [];
        foreach ($this->items as $id => $item) {
            $root = $this->find($parent, $id);
            $clusters[$root]['names'][] = $item;
            $clusters[$root]['words'][] = $item['words'];
        }

        $pairs = [];
        $roots = array_keys($clusters);
        foreach ($roots as $i => $a) {
            foreach ($roots as $j => $b) {
                if ($a === $b) {
                    continue;
                }
                if ($this->contained($clusters[$a]['words'], $clusters[$b]['words'])) {
                    $reason = 'Contained';
                } elseif ($i < $j && ! $this->contained($clusters[$b]['words'], $clusters[$a]['words'])
                    && $this->misspelled($clusters[$a]['words'], $clusters[$b]['words'])) {
                    $reason = 'Spelling';
                } else {
                    continue;
                }
                $pairs[] = [
                    'first' => $clusters[$a]['names'],
                    'second' => $clusters[$b]['names'],
                    'reason' => $reason,
                    'mills' => array_sum(array_column($clusters[$a]['names'], 'mills'))
                        + array_sum(array_column($clusters[$b]['names'], 'mills')),
                ];
            }
        }

        usort($pairs, fn ($x, $y) => $y['mills'] <=> $x['mills']);

        return $pairs;
    }

    /**
     * Lowercase words with punctuation, plurals and filler words set aside: "Pulp - Paper" and "Pulp & Paper" both
     * become [paper, pulp]. Sorted, so word order doesn't matter.
     *
     * @return list<string>
     */
    public static function words(string $name): array
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower($name), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_map(fn ($w) => strlen($w) > 3 && str_ends_with($w, 's') && ! str_ends_with($w, 'ss') ? substr($w, 0, -1) : $w, $words);
        $words = array_values(array_unique(array_diff($words, self::IGNORED_WORDS)));
        sort($words);

        return $words;
    }

    /* ------------------------------------------------------------------------------------------------------------ */

    /**
     * Union-find over the likely-same pairs. Returns the parent map and the reasons found, keyed by group root.
     *
     * @return array{0: array<int, int>, 1: array<int, list<string>>}
     */
    private function cluster(): array
    {
        $parent = array_combine(array_keys($this->items), array_keys($this->items));
        $found = [];

        $ids = array_keys($this->items);
        foreach ($ids as $i => $a) {
            foreach (array_slice($ids, $i + 1) as $b) {
                if ($reason = $this->sameReason($this->items[$a], $this->items[$b])) {
                    $found[] = [$a, $b, $reason];
                    $ra = $this->find($parent, $a);
                    $rb = $this->find($parent, $b);
                    if ($ra !== $rb) {
                        $parent[$rb] = $ra;
                    }
                }
            }
        }

        $reasons = [];
        foreach ($found as [$a, , $reason]) {
            $reasons[$this->find($parent, $a)][] = $reason;
        }

        return [$parent, $reasons];
    }

    private function find(array &$parent, int $id): int
    {
        while ($parent[$id] !== $id) {
            $id = $parent[$id] = $parent[$parent[$id]];
        }

        return $id;
    }

    private function sameReason(array $a, array $b): ?string
    {
        if (! $a['words'] || ! $b['words']) {
            return null;
        }

        if ($a['words'] === $b['words']) {
            return 'Same words';
        }

        if ($this->abbreviates($a, $b) || $this->abbreviates($b, $a)) {
            return 'Abbreviation';
        }

        return null;
    }

    /**
     * True when some name in group A is a letter or two away from some name in group B. Names under 5 letters are
     * skipped: nearly every short word is one letter from another ("Pole" and "Post").
     *
     * @param  list<list<string>>  $a
     * @param  list<list<string>>  $b
     */
    private function misspelled(array $a, array $b): bool
    {
        foreach ($a as $x) {
            foreach ($b as $y) {
                $x1 = implode(' ', $x);
                $y1 = implode(' ', $y);
                $shortest = min(strlen($x1), strlen($y1));
                $allowed = $shortest >= 10 ? 2 : ($shortest >= 5 ? 1 : 0);
                if ($allowed && $x1 !== $y1 && levenshtein($x1, $y1) <= $allowed) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * True when the one-word name is the initials of the other ("OSB" and "Oriented Strand Board").
     */
    private function abbreviates(array $short, array $long): bool
    {
        return 1 === count($short['words']) && count($long['words']) >= 2
            && $short['words'][0] === $long['initials'];
    }

    /**
     * First letters of the name's words, in their original order: "Oriented Strand Board" gives "osb".
     */
    public static function initials(string $name): string
    {
        $words = preg_split('/[^a-z0-9]+/', Str::lower($name), -1, PREG_SPLIT_NO_EMPTY);

        return implode('', array_map(fn ($w) => $w[0], array_diff($words, self::IGNORED_WORDS)));
    }

    /**
     * True when some name in group A has all its words inside some name in group B, and B's is longer.
     *
     * @param  list<list<string>>  $a
     * @param  list<list<string>>  $b
     */
    private function contained(array $a, array $b): bool
    {
        foreach ($a as $short) {
            foreach ($b as $long) {
                if ($short && count($short) < count($long) && ! array_diff($short, $long)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Every string literal in the mapper classes, lowercased, mapped to the mappers that contain it. Comments are
     * skipped by the tokenizer, so commented-out mappings don't count.
     *
     * @return array<string, list<string>>
     */
    public static function mapperStrings(): array
    {
        $mentions = [];
        foreach (glob(app_path('Mappers/*MillMapper.php')) ?: [] as $file) {
            $label = Str::of(basename($file, '.php'))->beforeLast('MillMapper')->headline()->toString();
            $label = 'Abstract' === $label ? 'All (shared)' : $label;

            foreach (token_get_all((string) file_get_contents($file)) as $token) {
                if (is_array($token) && T_CONSTANT_ENCAPSED_STRING === $token[0]) {
                    $value = Str::lower(trim(stripcslashes(substr($token[1], 1, -1))));
                    if ('' !== $value) {
                        $mentions[$value][$label] = $label;
                    }
                }
            }
        }

        return array_map(fn ($labels) => array_values($labels), $mentions);
    }
}
