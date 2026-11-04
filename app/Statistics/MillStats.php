<?php

namespace App\Statistics;

use App\Models\County;
use App\Models\Mill;
use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Numbers about published (approved) mills, for one state or, with no state, the whole region.
 */
final class MillStats
{
    /**
     * The fields counted for data completeness: label, and the SQL condition for "this mill has it".
     * Blank strings count as missing. Email and phone count when either of their two columns is filled.
     */
    public const array FIELDS = [
        'email' => ['Email', "COALESCE(NULLIF(TRIM(mills.email), ''), NULLIF(TRIM(mills.email_2), '')) IS NOT NULL"],
        'phone' => ['Phone', "COALESCE(NULLIF(TRIM(mills.telephone), ''), NULLIF(TRIM(mills.telephone_2), '')) IS NOT NULL"],
        'website' => ['Website', "NULLIF(TRIM(mills.web_site), '') IS NOT NULL"],
        'contact' => ['Contact name', "NULLIF(TRIM(mills.contact_name), '') IS NOT NULL"],
        'address' => ['Street address', "NULLIF(TRIM(mills.physical_address), '') IS NOT NULL"],
        'coordinates' => ['Map location', 'mills.latitude IS NOT NULL AND mills.longitude IS NOT NULL'],
        'county' => ['County', 'mills.county_id IS NOT NULL'],
        'types' => ['Mill type', 'EXISTS (SELECT 1 FROM mill_mill_type WHERE mill_mill_type.mill_id = mills.id)'],
        'species' => ['Wood species', 'EXISTS (SELECT 1 FROM mill_wood_species WHERE mill_wood_species.mill_id = mills.id)'],
        'size' => ['Size', "NULLIF(TRIM(mills.size), '') IS NOT NULL"],
    ];

    /**
     * Age buckets by days since a mill was last updated: [label, newer than this many days ago].
     * The last bucket catches everything older.
     */
    public const array AGE_BUCKETS = [
        ['Within 30 days', 30],
        ['31–90 days', 90],
        ['91–180 days', 180],
        ['181–365 days', 365],
        ['Over a year', null],
    ];

    /**
     * The look-back windows stakeholders asked for (days).
     */
    public const array WINDOWS = [30, 90, 180, 365];

    public function __construct(public readonly ?int $stateId = null) {}

    public static function for(?State $state): self
    {
        return new self($state?->id);
    }

    /**
     * Published mills, limited to this state when there is one.
     */
    public function mills(): Builder
    {
        return Mill::query()->when($this->stateId, fn (Builder $q) => $q->where('mills.state_id', $this->stateId));
    }

    public function total(): int
    {
        return $this->mills()->count();
    }

    /**
     * @return array{total: int, complete: int, fields: array<string, array{label: string, count: int, percent: float}>}
     */
    public function completeness(): array
    {
        return self::completenessFromCounts($this->completenessCounts($this->mills())->first());
    }

    /**
     * Completeness for every state with mills, keyed by state id. One query.
     *
     * @return array<int, array{total: int, complete: int, fields: array<string, array{label: string, count: int, percent: float}>}>
     */
    public static function completenessByState(): array
    {
        $rows = (new self)->completenessCounts(Mill::query()->whereNotNull('mills.state_id'))
            ->addSelect('mills.state_id')
            ->groupBy('mills.state_id')
            ->get();

        return $rows->mapWithKeys(fn ($row) => [(int) $row->state_id => self::completenessFromCounts($row)])->all();
    }

    /**
     * Raw counts: total, complete, and one column per field.
     */
    private function completenessCounts(Builder $query): \Illuminate\Database\Query\Builder
    {
        $selects = ['COUNT(*) AS total'];
        foreach (self::FIELDS as $key => [, $condition]) {
            $selects[] = "SUM(CASE WHEN {$condition} THEN 1 ELSE 0 END) AS {$key}";
        }
        $all = implode(' AND ', array_map(fn ($field) => "({$field[1]})", self::FIELDS));
        $selects[] = "SUM(CASE WHEN {$all} THEN 1 ELSE 0 END) AS complete";

        return $query->toBase()->selectRaw(implode(', ', $selects));
    }

    private static function completenessFromCounts(?object $row): array
    {
        $total = (int) ($row->total ?? 0);
        $fields = [];
        foreach (self::FIELDS as $key => [$label]) {
            $count = (int) ($row->{$key} ?? 0);
            $fields[$key] = ['label' => $label, 'count' => $count, 'percent' => self::percent($count, $total)];
        }

        return ['total' => $total, 'complete' => (int) ($row->complete ?? 0), 'fields' => $fields];
    }

    /**
     * Mills per age bucket, by when they were last updated in this system. Imports count as updates.
     *
     * @return array<string, int> label => count, in AGE_BUCKETS order
     */
    public function dataAge(): array
    {
        return self::ageFromCounts($this->ageCounts($this->mills())->first());
    }

    /**
     * @return array<int, array<string, int>> state id => (label => count)
     */
    public static function dataAgeByState(): array
    {
        return (new self)->ageCounts(Mill::query()->whereNotNull('mills.state_id'))
            ->addSelect('mills.state_id')
            ->groupBy('mills.state_id')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->state_id => self::ageFromCounts($row)])
            ->all();
    }

    private function ageCounts(Builder $query): \Illuminate\Database\Query\Builder
    {
        $selects = ['COUNT(*) AS total'];
        $bindings = [];
        foreach (self::AGE_BUCKETS as $i => [, $days]) {
            if (null !== $days) {
                $selects[] = "SUM(CASE WHEN mills.updated_at >= ? THEN 1 ELSE 0 END) AS newer_{$i}";
                $bindings[] = now()->subDays($days);
            }
        }

        return $query->toBase()->selectRaw(implode(', ', $selects), $bindings);
    }

    /**
     * The query counts mills newer than each cut-off; each bucket is the difference from the one before.
     */
    private static function ageFromCounts(?object $row): array
    {
        $buckets = [];
        $previous = 0;
        foreach (self::AGE_BUCKETS as $i => [$label, $days]) {
            $newer = null === $days ? (int) ($row->total ?? 0) : (int) ($row->{"newer_{$i}"} ?? 0);
            $buckets[$label] = $newer - $previous;
            $previous = $newer;
        }

        return $buckets;
    }

    /**
     * Mills per state, by state name (alphabetical).
     *
     * @return array<string, int>
     */
    public static function countsByState(): array
    {
        return Mill::query()->toBase()
            ->join('states', 'states.id', '=', 'mills.state_id')
            ->selectRaw('states.name, COUNT(*) AS mills')
            ->groupBy('states.name')
            ->orderBy('states.name')
            ->pluck('mills', 'name')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Mills added (created_at) and updated (updated_at) in each window, by state name, plus a region total.
     * Imports set both dates, so a state that re-imported recently shows most of its mills as updated.
     *
     * @return array<string, array{mills: int, added: array<int, int>, updated: array<int, int>}>
     */
    public static function recentChangesByState(): array
    {
        $selects = ['states.name', 'COUNT(*) AS mills'];
        $bindings = [];
        foreach (['added' => 'created_at', 'updated' => 'updated_at'] as $kind => $column) {
            foreach (self::WINDOWS as $days) {
                $selects[] = "SUM(CASE WHEN mills.{$column} >= ? THEN 1 ELSE 0 END) AS {$kind}_{$days}";
                $bindings[] = now()->subDays($days);
            }
        }

        $rows = Mill::query()->toBase()
            ->join('states', 'states.id', '=', 'mills.state_id')
            ->selectRaw(implode(', ', $selects), $bindings)
            ->groupBy('states.name')
            ->orderBy('states.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $entry = ['mills' => (int) $row->mills, 'added' => [], 'updated' => []];
            foreach (['added', 'updated'] as $kind) {
                foreach (self::WINDOWS as $days) {
                    $entry[$kind][$days] = (int) $row->{"{$kind}_{$days}"};
                }
            }
            $result[$row->name] = $entry;
        }

        return $result;
    }

    /**
     * Mills per mill type, most common first. A mill with several types counts once for each.
     *
     * @return array<string, int>
     */
    public function byType(): array
    {
        return $this->countThroughPivot('mill_mill_type', 'mill_type_id', 'mill_types');
    }

    /**
     * @return array<string, int>
     */
    public function bySpecies(): array
    {
        return $this->countThroughPivot('mill_wood_species', 'wood_species_id', 'wood_species');
    }

    private function countThroughPivot(string $pivot, string $foreignKey, string $table): array
    {
        return $this->mills()->toBase()
            ->join($pivot, "{$pivot}.mill_id", '=', 'mills.id')
            ->join($table, "{$table}.id", '=', "{$pivot}.{$foreignKey}")
            ->selectRaw("{$table}.name AS name, COUNT(*) AS mills")
            ->groupBy("{$table}.name")
            ->orderByDesc('mills')
            ->orderBy('name')
            ->pluck('mills', 'name')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Mill types per state: state name => (type name => mills).
     *
     * @return array<string, array<string, int>>
     */
    public static function typesByState(): array
    {
        $rows = Mill::query()->toBase()
            ->join('states', 'states.id', '=', 'mills.state_id')
            ->join('mill_mill_type', 'mill_mill_type.mill_id', '=', 'mills.id')
            ->join('mill_types', 'mill_types.id', '=', 'mill_mill_type.mill_type_id')
            ->selectRaw('states.name AS state, mill_types.name AS type, COUNT(*) AS mills')
            ->groupBy('states.name', 'mill_types.name')
            ->orderBy('states.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->state][$row->type] = (int) $row->mills;
        }

        return $result;
    }

    /**
     * Mills per size as entered. Sizes are free text from each state's source data, so different states use
     * different scales (Small/Medium/Large, 1-5, A-F...). Blank sizes are grouped as "Not given".
     *
     * @return array<string, int>
     */
    public function bySize(): array
    {
        return $this->mills()->toBase()
            ->selectRaw("COALESCE(NULLIF(TRIM(mills.size), ''), 'Not given') AS size_label, COUNT(*) AS mills")
            ->groupBy('size_label')
            ->orderByDesc('mills')
            ->pluck('mills', 'size_label')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /**
     * Every county in the state with its mill count (zero included), for the county map and table.
     *
     * @return Collection<int, array{fips: ?string, name: string, mills: int}> by name
     */
    public function byCounty(): Collection
    {
        if (! $this->stateId) {
            return collect();
        }

        $counts = $this->mills()->toBase()
            ->whereNotNull('mills.county_id')
            ->selectRaw('mills.county_id, COUNT(*) AS mills')
            ->groupBy('mills.county_id')
            ->pluck('mills', 'county_id');

        return County::query()
            ->where('state_id', $this->stateId)
            ->orderBy('name')
            ->get(['id', 'name', 'state_code', 'fips_code'])
            ->map(fn (County $county) => [
                'fips' => self::fips($county->state_code, $county->fips_code),
                'name' => $county->name,
                'mills' => (int) ($counts[$county->id] ?? 0),
            ]);
    }

    /**
     * The 5-digit county FIPS code used by the county map, or null when either part is missing.
     */
    public static function fips(?string $stateCode, ?string $countyCode): ?string
    {
        if (! is_numeric($stateCode) || ! is_numeric($countyCode)) {
            return null;
        }

        return str_pad($stateCode, 2, '0', STR_PAD_LEFT).str_pad($countyCode, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Map points for the county map: [latitude, longitude, name].
     *
     * @return list<array{0: float, 1: float, 2: string}>
     */
    public function points(): array
    {
        return $this->mills()->toBase()
            ->whereNotNull('mills.latitude')
            ->whereNotNull('mills.longitude')
            ->get(['mills.latitude', 'mills.longitude', 'mills.mill_name'])
            ->map(fn ($m) => [round((float) $m->latitude, 5), round((float) $m->longitude, 5), (string) $m->mill_name])
            ->all();
    }

    /**
     * Mills added per month over the last $months months (oldest first), from created_at.
     *
     * @return array{months: array<string, int>, unknown: int} unknown: mills with no created_at at all
     */
    public function addedPerMonth(int $months = 24): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);

        $counts = $this->mills()->toBase()
            ->where('mills.created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(mills.created_at, '%Y-%m') AS month, COUNT(*) AS mills")
            ->groupBy('month')
            ->pluck('mills', 'month');

        return [
            'months' => self::fillMonths($start, $months, fn ($key) => (int) ($counts[$key] ?? 0)),
            'unknown' => $this->mills()->whereNull('mills.created_at')->count(),
        ];
    }

    /**
     * Mills flagged for follow-up, and gaps that keep a mill off the map or out of county searches.
     *
     * @return array<string, int>
     */
    public function needsAttention(): array
    {
        $row = $this->mills()->toBase()->selectRaw(
            'SUM(CASE WHEN mills.needs_review = 1 THEN 1 ELSE 0 END) AS needs_review, '
            .'SUM(CASE WHEN mills.needs_geocode = 1 THEN 1 ELSE 0 END) AS needs_geocode, '
            .'SUM(CASE WHEN mills.latitude IS NULL OR mills.longitude IS NULL THEN 1 ELSE 0 END) AS no_location, '
            .'SUM(CASE WHEN mills.county_id IS NULL THEN 1 ELSE 0 END) AS no_county'
        )->first();

        return [
            'Flagged for review' => (int) ($row->needs_review ?? 0),
            'Waiting for geocoding' => (int) ($row->needs_geocode ?? 0),
            'Not on the map (no location)' => (int) ($row->no_location ?? 0),
            'No county' => (int) ($row->no_county ?? 0),
        ];
    }

    /**
     * The mills that have gone longest without an update.
     *
     * @return Collection<int, Mill>
     */
    public function leastRecentlyUpdated(int $limit = 10): Collection
    {
        return $this->mills()
            ->orderBy('mills.updated_at')
            ->orderBy('mills.id')
            ->limit($limit)
            ->get(['mills.id', 'mills.mill_name', 'mills.physical_city', 'mills.updated_at']);
    }

    public static function percent(int|float $part, int|float $whole): float
    {
        return $whole > 0 ? round($part / $whole * 100, 1) : 0.0;
    }

    /**
     * 'Y-m' => value for each of $count months from $start.
     *
     * @return array<string, mixed>
     */
    public static function fillMonths(Carbon $start, int $count, callable $value): array
    {
        $months = [];
        $month = $start->copy()->startOfMonth();
        for ($i = 0; $i < $count; $i++) {
            $key = $month->format('Y-m');
            $months[$key] = $value($key);
            $month->addMonth();
        }

        return $months;
    }

    /**
     * Fold everything after the first $keep entries into one entry. Its default label isn't "Other", because
     * "Other" is also a mill type.
     *
     * @param  array<string, int>  $counts  sorted most common first
     * @return array<string, int>
     */
    public static function topWithOther(array $counts, int $keep, string $other = 'All others'): array
    {
        if (count($counts) <= $keep + 1) {
            return $counts;
        }

        $top = array_slice($counts, 0, $keep, true);
        $top[$other] = ($top[$other] ?? 0) + array_sum(array_slice($counts, $keep, null, true));

        return $top;
    }
}
