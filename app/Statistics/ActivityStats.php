<?php

namespace App\Statistics;

use App\Models;
use App\Models\Import;
use App\Models\State;
use App\Support\ActivitySource;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Change history from the activity log (spatie/laravel-activitylog). The log started when it was deployed, so
 * these charts only cover changes from then on.
 *
 * With a state, only changes to that state's mills (and its import summaries) are counted.
 */
final class ActivityStats
{
    /**
     * Sources in chart order, with labels. Keys are ActivitySource values (activity_log.log_name).
     */
    public const array SOURCES = [
        ActivitySource::ADMIN => 'Admin edits',
        ActivitySource::PUBLIC_SUBMISSION => 'Approved public submissions',
        ActivitySource::IMPORT => 'Imports',
        ActivitySource::SYSTEM => 'Background jobs',
        ActivitySource::WEB => 'Public website',
    ];

    /**
     * Kinds of record, for "what changed": label => subject classes.
     */
    public const array KINDS = [
        'Mills' => [Models\Mill::class],
        'State pages' => [
            Models\StatePage::class, Models\StateContact::class, Models\StateForestOverview::class,
            Models\StateForestType::class, Models\StateForestProduct::class, Models\StateEconomicImpact::class,
            Models\StateForestryAgency::class, Models\StateAssistanceCategory::class, Models\StateAssistanceLink::class,
        ],
        'FAQs' => [Models\Faq::class, Models\FaqCategory::class],
        'Mill types & species' => [Models\MillType::class, Models\WoodSpecies::class],
        'Users & roles' => [Models\User::class, Models\Role::class, Models\Permission::class],
        'Other' => [Models\State::class, Models\County::class, Models\PageSeo::class, Import::class],
    ];

    public function __construct(public readonly ?int $stateId = null) {}

    public static function for(?State $state): self
    {
        return new self($state?->id);
    }

    /**
     * Activity entries in scope. For a state: changes to its mills (joined by subject id; changes to mills deleted
     * since then can't be placed in a state) and its imports' summaries.
     */
    private function entries(): Builder
    {
        $query = DB::table('activity_log');

        if (! $this->stateId) {
            return $query;
        }

        return $query->where(function (Builder $q) {
            $q->where(fn (Builder $mill) => $mill
                ->where('activity_log.subject_type', Models\Mill::class)
                ->whereIn('activity_log.subject_id', DB::table('mills')->select('id')->where('state_id', $this->stateId)))
                ->orWhere(fn (Builder $import) => $import
                    ->where('activity_log.subject_type', Import::class)
                    ->whereIn('activity_log.subject_id', DB::table('imports')->select('id')->where('state_id', $this->stateId)));
        });
    }

    /**
     * Changes per month by source.
     *
     * @return array<string, array<string, int>> source label => ('Y-m' => changes)
     */
    public function perMonthBySource(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = $this->entries()
            ->where('activity_log.created_at', '>=', $start)
            ->selectRaw("activity_log.log_name, DATE_FORMAT(activity_log.created_at, '%Y-%m') AS month, COUNT(*) AS changes")
            ->groupBy('activity_log.log_name', 'month')
            ->get();

        $result = [];
        foreach (self::SOURCES + $this->otherSources($rows) as $source => $label) {
            $result[$label] = MillStats::fillMonths($start, $months, fn ($key) => (int) $rows
                ->where('log_name', $source)->where('month', $key)->sum('changes'));
        }

        return array_filter($result, fn ($months) => array_sum($months) > 0);
    }

    /**
     * Log names that aren't in SOURCES (e.g. "default" from before sources were labelled), shown as themselves.
     */
    private function otherSources(Collection $rows): array
    {
        return $rows->pluck('log_name')->unique()
            ->reject(fn ($name) => isset(self::SOURCES[$name]))
            ->mapWithKeys(fn ($name) => [$name => ucfirst((string) $name)])
            ->all();
    }

    /**
     * Changes per month by kind of record (region-wide only).
     *
     * @return array<string, array<string, int>> kind => ('Y-m' => changes)
     */
    public function perMonthByKind(int $months = 12): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = $this->entries()
            ->where('activity_log.created_at', '>=', $start)
            ->selectRaw("activity_log.subject_type, DATE_FORMAT(activity_log.created_at, '%Y-%m') AS month, COUNT(*) AS changes")
            ->groupBy('activity_log.subject_type', 'month')
            ->get();

        $result = [];
        foreach (self::KINDS as $kind => $classes) {
            $result[$kind] = MillStats::fillMonths($start, $months, fn ($key) => (int) $rows
                ->whereIn('subject_type', $classes)->where('month', $key)->sum('changes'));
        }

        return array_filter($result, fn ($months) => array_sum($months) > 0);
    }

    /**
     * Which mill fields get changed most, over the last $days days, by source.
     * Relation changes (mill types, wood species) are logged as their own entries and counted here too.
     *
     * @return array<string, array<string, int>> field label => (source label => changes), most changed first
     */
    public function millFieldsChanged(int $days = 365): array
    {
        $counts = [];
        $this->entries()
            ->where('activity_log.subject_type', Models\Mill::class)
            ->where('activity_log.event', 'updated')
            ->where('activity_log.created_at', '>=', now()->subDays($days))
            ->select(['activity_log.id', 'activity_log.log_name', 'activity_log.properties'])
            ->orderBy('activity_log.id')
            ->chunk(500, function (Collection $rows) use (&$counts) {
                foreach ($rows as $row) {
                    $properties = json_decode((string) $row->properties, true) ?: [];
                    $source = self::SOURCES[$row->log_name] ?? ucfirst((string) $row->log_name);
                    foreach (array_keys($properties['attributes'] ?? []) as $field) {
                        $label = SubmissionStats::fieldLabel(match ($field) {
                            'millTypes' => 'mill_types',
                            'woodSpecies' => 'wood_species',
                            default => $field,
                        });
                        $counts[$label][$source] = ($counts[$label][$source] ?? 0) + 1;
                    }
                }
            });

        uasort($counts, fn ($a, $b) => array_sum($b) <=> array_sum($a));

        return $counts;
    }

    /**
     * Changes in the last $days days.
     */
    public function changesSince(int $days): int
    {
        return $this->entries()->where('activity_log.created_at', '>=', now()->subDays($days))->count();
    }

    /**
     * Distinct mills added or changed by people in the last $days days: admin edits and approved public submissions.
     * Imports and background jobs (geocoding, maintenance) don't count.
     */
    public function millsEditedSince(int $days): int
    {
        return $this->entries()
            ->where('activity_log.subject_type', Models\Mill::class)
            ->whereIn('activity_log.log_name', [ActivitySource::ADMIN, ActivitySource::PUBLIC_SUBMISSION])
            ->where('activity_log.created_at', '>=', now()->subDays($days))
            ->distinct()
            ->count('activity_log.subject_id');
    }

    /**
     * The most recent entries in scope, newest first.
     *
     * @return Collection<int, object>
     */
    public function recent(int $limit = 15): Collection
    {
        return $this->entries()
            ->leftJoin('users', fn ($join) => $join
                ->on('users.id', '=', 'activity_log.causer_id')
                ->where('activity_log.causer_type', Models\User::class))
            ->orderByDesc('activity_log.id')
            ->limit($limit)
            ->get([
                'activity_log.id', 'activity_log.log_name', 'activity_log.event', 'activity_log.description',
                'activity_log.subject_type', 'activity_log.subject_id', 'activity_log.created_at', 'users.name AS causer',
            ])
            ->map(function ($row) {
                $row->source = self::SOURCES[$row->log_name] ?? ucfirst((string) $row->log_name);
                $row->kind = class_basename((string) $row->subject_type);

                return $row;
            });
    }

    /**
     * The date of the first logged change, or null.
     */
    public static function firstEntry(): ?\Illuminate\Support\Carbon
    {
        $first = DB::table('activity_log')->min('created_at');

        return $first ? \Illuminate\Support\Carbon::parse($first) : null;
    }

    /**
     * Imports per month and the rows they brought in (from the imports table, which predates the activity log).
     *
     * @return array{imports: array<string, int>, rows: array<string, int>}
     */
    public function importsPerMonth(int $months = 24): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $rows = DB::table('imports')
            ->when($this->stateId, fn (Builder $q) => $q->where('state_id', $this->stateId))
            ->where('created_at', '>=', $start)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') AS month, COUNT(*) AS imports, SUM(total_rows) AS total_rows")
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        return [
            'imports' => MillStats::fillMonths($start, $months, fn ($key) => (int) ($rows[$key]->imports ?? 0)),
            'rows' => MillStats::fillMonths($start, $months, fn ($key) => (int) ($rows[$key]->total_rows ?? 0)),
        ];
    }

    /**
     * The most recent imports, newest first.
     *
     * @return Collection<int, Import>
     */
    public function recentImports(int $limit = 10): Collection
    {
        return Import::query()
            ->with(['state:id,name', 'user:id,name'])
            ->when($this->stateId, fn ($q) => $q->where('state_id', $this->stateId))
            ->orderByDesc('id')
            ->limit($limit)
            ->get(['id', 'state_id', 'user_id', 'status', 'total_rows', 'imported_rows', 'failed_rows', 'original_file_name', 'api_url', 'created_at']);
    }
}
