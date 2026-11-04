<?php

namespace App\Http\Controllers\Admin;

use App\Models\Import;
use App\Models\State;
use App\Statistics\ActivityStats;
use App\Statistics\Chart;
use App\Statistics\MillStats;
use App\Statistics\SimilarNames;
use App\Statistics\Snapshots;
use App\Statistics\StatsAccess;
use App\Statistics\SubmissionStats;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Admin Statistics: Overview (region), By State (one state), Submissions, Activity, Similar Names, and Compare States.
 *
 * Everything takes statistics.see. Compare States, which puts states' completeness and review times side by side,
 * also takes statistics.compare_states. State Agents can open By State for their own state only.
 * The numbers come from App\Statistics; this controller only turns them into charts and tables.
 */
class StatisticsController extends Controller
{
    /**
     * Categorical slots for mill types across states: seven types, then "All others" in gray.
     */
    private const int TYPES_SHOWN = 7;

    public function index()
    {
        $access = $this->authorizeStats();
        Snapshots::ensureToday();

        $stats = new MillStats;
        $states = State::has('mills')->orderBy('name')->get(['id', 'name', 'abbreviation']);
        $counts = MillStats::countsByState();
        $recent = MillStats::recentChangesByState();
        $snapshots = Snapshots::since(now()->subDays(365));
        $submissions = (new SubmissionStats)->totals();

        $stateLink = fn (string $name) => $this->stateLink($access, $states->firstWhere('name', $name));

        $millsByState = Chart::bar('mills-by-state', 'Mills in each state')
            ->describe('Published mills, by state.')
            ->labels(array_keys($counts), 'State')
            ->series('Mills', array_values($counts), 'series-1', array_map($stateLink, array_keys($counts)))
            ->horizontal()
            ->fitBars();

        // mill types across states: the region's most common types, the rest folded into "All others"
        $regionTypes = $stats->byType();
        $topTypes = array_slice(array_keys($regionTypes), 0, self::TYPES_SHOWN);
        $typesByState = MillStats::typesByState();
        $typesAcrossStates = Chart::bar('types-across-states', 'Mill types in each state')
            ->describe('Mills of the region’s '.count($topTypes).' most common types, by state. A mill with several types counts once for each.')
            ->labels(array_keys($typesByState), 'State')
            ->horizontal()
            ->stacked();
        foreach ($topTypes as $type) {
            $typesAcrossStates->series($type, array_map(fn ($types) => $types[$type] ?? 0, array_values($typesByState)));
        }
        $typesAcrossStates
            ->series('All others', array_map(fn ($types) => array_sum(array_diff_key($types, array_flip($topTypes))), array_values($typesByState)), 'other')
            ->fitBars(32);

        return $this->page('index', 'Overview', $access, [
            'kpis' => [
                ['label' => 'Published mills', 'value' => $stats->total()],
                ['label' => 'States with mills', 'value' => count($counts)],
                ['label' => 'Counties with mills', 'value' => $stats->mills()->whereNotNull('county_id')->distinct()->count('county_id')],
                ['label' => 'Added in the last year', 'value' => array_sum(array_map(fn ($s) => $s['added'][365], $recent))],
                ['label' => 'Pending public submissions', 'value' => $submissions['pending'], 'link' => backpack_url('statistics/submissions')],
                ['label' => 'Changes logged, last 30 days', 'value' => (new ActivityStats)->changesSince(30), 'link' => backpack_url('statistics/activity')],
            ],
            'millsByState' => $millsByState,
            'stateTrends' => $this->stateTrends($states, $snapshots, $access),
            'totalOverTime' => $this->overTime('total-mills-over-time', 'Total mills over time', $snapshots,
                fn (Collection $day) => $day->sum(fn ($s) => $s->metrics['mills'] ?? 0), 'Mills'),
            'recent' => $recent,
            'windows' => MillStats::WINDOWS,
            'typesAcrossStates' => $typesAcrossStates,
            'typesHeat' => $this->typesHeat($typesByState, array_slice(array_keys($regionTypes), 0, 10)),
            'types' => $this->countsChart('types', 'Mill types', 'Mills of each type across the region. A mill with several types counts once for each.', $regionTypes, 'Mill type', 15),
            'species' => $this->countsChart('species', 'Wood species', 'Mills that work with each species across the region.', $stats->bySpecies(), 'Species', 15),
            'added' => $this->addedChart($stats),
            'dataAge' => $this->dataAgeChart('data-age', $stats->dataAge()),
            'completeness' => $this->completenessChart('completeness', $stats->completeness()),
            'historyStarts' => Snapshots::firstDay(),
            'stateLink' => $stateLink,
        ]);
    }

    public function state(?string $state = null)
    {
        $access = $this->authorizeStats();
        // the state picker submits ?state=
        $state ??= request()->query('state');
        $selected = $access->resolveState($state);
        if (! $selected) {
            abort(null === $state ? 404 : 403);
        }
        Snapshots::ensureToday();

        $stats = MillStats::for($selected);
        $completeness = $stats->completeness();
        $snapshots = Snapshots::since(now()->subDays(365), $selected->id);
        $counties = $stats->byCounty();
        $types = $stats->byType();
        $submissions = SubmissionStats::for($selected);
        $submissionTotals = $submissions->totals();
        $activity = ActivityStats::for($selected);
        $lastImport = Import::where('state_id', $selected->id)->latest('id')->first(['id', 'created_at', 'status']);
        $millsThen = $this->snapshotValue($snapshots, 30, fn ($s) => $s->metrics['mills'] ?? 0);

        $millListUrl = backpack_url('mill').'?'.http_build_query(['state_id' => $selected->id]);

        return $this->page('state', $selected->name, $access, [
            'state' => $selected,
            'states' => $access->states(),
            'kpis' => [
                [
                    'label' => 'Published mills',
                    'value' => $completeness['total'],
                    'detail' => null === $millsThen ? null : self::signed($completeness['total'] - $millsThen).' vs 30 days ago',
                    'link' => $millListUrl,
                ],
                ['label' => 'Mills with every key field', 'value' => MillStats::percent($completeness['complete'], $completeness['total']).'%',
                    'detail' => number_format($completeness['complete']).' of '.number_format($completeness['total'])],
                ['label' => 'Counties with mills', 'value' => $counties->where('mills', '>', 0)->count(), 'detail' => 'of '.$counties->count()],
                ['label' => 'Pending public submissions', 'value' => $submissionTotals['pending'],
                    'link' => backpack_url('statistics/submissions').'?state='.$selected->abbreviation],
                ['label' => 'Mills edited, last 90 days', 'value' => $activity->millsEditedSince(90), 'detail' => 'by admins and public submissions',
                    'link' => backpack_url('statistics/activity').'?state='.$selected->abbreviation],
                ['label' => 'Last import', 'value' => $lastImport?->created_at?->format('M j, Y') ?? 'None'],
            ],
            'millListUrl' => $millListUrl,
            'completeness' => $this->completenessChart('completeness', $completeness),
            'completenessOverTime' => $this->completenessOverTime($snapshots),
            'millsOverTime' => $this->overTime('mills-over-time', 'Mills over time', $snapshots,
                fn (Collection $day) => $day->sum(fn ($s) => $s->metrics['mills'] ?? 0), 'Mills'),
            'dataAge' => $this->dataAgeChart('data-age', $stats->dataAge()),
            'types' => $this->countsChart('types', 'Mill types', 'A mill with several types counts once for each.', $types, 'Mill type', 15),
            'typesOverTime' => $this->typesOverTime($snapshots, array_slice(array_keys($types), 0, 5)),
            'species' => $this->countsChart('species', 'Wood species', 'Mills that work with each species.', $stats->bySpecies(), 'Species', 15),
            'sizes' => $this->countsChart('sizes', 'Mill sizes', 'Sizes as entered in the state’s source data.', $stats->bySize(), 'Size', 12),
            'added' => $this->addedChart($stats),
            'pendingAges' => Chart::bar('pending-ages', 'How long pending submissions have waited')
                ->labels(array_keys($pending = $submissions->pendingAges()), 'Waiting')
                ->series('Submissions', array_values($pending), 'series-1')
                ->whenEmpty('No submissions are waiting for review.'),
            'activityBySource' => $this->bySourceChart($activity),
            'map' => $this->mapData($selected, $counties, $stats),
            'counties' => $counties,
            'attention' => $stats->needsAttention(),
            'leastRecent' => $stats->leastRecentlyUpdated(),
            'canEditMills' => backpack_user()->can('mills.edit'),
            'sections' => $this->stateSections($selected),
            'canSeeStateHub' => backpack_user()->canAny(StateHubController::permissions()),
            'historyStarts' => Snapshots::firstDay(),
        ]);
    }

    public function submissions(Request $request)
    {
        $access = $this->authorizeStats();
        $selected = $access->filterState($request->query('state'));
        $stats = SubmissionStats::for($selected);
        $totals = $stats->totals();
        $times = $stats->reviewTimes();
        $received = $stats->receivedPerMonth();
        $reviewed = $stats->reviewedPerMonth();
        $fields = $stats->fieldsChanged();

        return $this->page('submissions', 'Public Submissions', $access, [
            'selected' => $selected,
            'states' => $access->states(),
            'kpis' => [
                ['label' => 'Pending', 'value' => $totals['pending']],
                ['label' => 'Approved', 'value' => $totals['approved']],
                ['label' => 'Rejected', 'value' => $totals['rejected']],
                ['label' => 'New mills suggested', 'value' => $totals['new'], 'detail' => number_format($totals['edits']).' '.Str::plural('correction', $totals['edits'])],
                ['label' => 'Typical time to review', 'value' => null === $times['median'] ? '–' : Chart::format($times['median']).' days',
                    'detail' => $times['reviews'] ? 'median of '.$times['reviews'].' '.Str::plural('review', $times['reviews']).' in the last year' : 'no reviews in the last year'],
            ],
            'received' => Chart::bar('received', 'Submissions received')
                ->describe('Per month: suggested new mills and corrections to listed mills.')
                ->months(array_keys($received['new']))
                ->series('New mills', array_values($received['new']))
                ->series('Corrections', array_values($received['edits']))
                ->stacked()
                ->whenEmpty('No submissions in the last two years.'),
            'reviewed' => Chart::bar('reviewed', 'Submissions reviewed')
                ->describe('Per month, by outcome.')
                ->months(array_keys($reviewed['approved']))
                ->series('Approved', array_values($reviewed['approved']))
                ->series('Rejected', array_values($reviewed['rejected']))
                ->stacked()
                ->whenEmpty('No reviews in the last two years.'),
            'pendingAges' => Chart::bar('pending-ages', 'How long pending submissions have waited')
                ->labels(array_keys($pending = $stats->pendingAges()), 'Waiting')
                ->series('Submissions', array_values($pending), 'series-1')
                ->whenEmpty('No submissions are waiting for review.'),
            'fields' => Chart::bar('fields-corrected', 'What the public corrects')
                ->describe('How many corrections changed each field.')
                ->labels(array_keys($fields), 'Field')
                ->series('Corrections', array_values($fields), 'series-1')
                ->horizontal()
                ->fitBars()
                ->whenEmpty('No corrections yet.'),
            'oldestPending' => $stats->oldestPending(),
            'stateNames' => State::pluck('name', 'id'),
        ]);
    }

    public function activity(Request $request)
    {
        $access = $this->authorizeStats();
        $selected = $access->filterState($request->query('state'));
        $stats = ActivityStats::for($selected);
        $byKind = $selected ? [] : $stats->perMonthByKind();
        $fields = $stats->millFieldsChanged();
        $imports = $stats->importsPerMonth();

        $kindChart = Chart::bar('by-kind', 'What changed')
            ->describe('Changes per month, by kind of record.')
            ->stacked()
            ->whenEmpty('Nothing has been logged yet.');
        $kindChart->months(array_keys(reset($byKind) ?: MillStats::fillMonths(now()->startOfMonth()->subMonths(11), 12, fn () => 0)));
        // each kind keeps its color whichever kinds have changes (color follows the kind, not its position)
        $kindSlots = array_flip(array_keys(ActivityStats::KINDS));
        foreach ($byKind as $kind => $months) {
            $kindChart->series($kind, array_values($months), 'series-'.($kindSlots[$kind] + 1));
        }

        $sources = array_values(array_unique(array_merge(...array_map('array_keys', array_values($fields ?: [[]])))));
        $fieldChart = Chart::bar('mill-fields', 'Mill fields changed most')
            ->describe('Changes to each mill field in the last year, by source.')
            ->labels(array_keys($fields), 'Field')
            ->horizontal()
            ->stacked()
            ->whenEmpty('No mill changes logged in the last year.');
        foreach (array_keys(ActivityStats::SOURCES) as $i => $key) {
            $label = ActivityStats::SOURCES[$key];
            if (in_array($label, $sources, true)) {
                $fieldChart->series($label, array_map(fn ($bySource) => $bySource[$label] ?? 0, array_values($fields)), 'series-'.($i + 1));
            }
        }
        $fieldChart->fitBars();

        return $this->page('activity', 'Activity', $access, [
            'selected' => $selected,
            'states' => $access->states(),
            'firstEntry' => ActivityStats::firstEntry(),
            'kpis' => [
                ['label' => 'Changes logged, last 30 days', 'value' => $stats->changesSince(30)],
                ['label' => 'Mills edited, last 30 days', 'value' => $stats->millsEditedSince(30), 'detail' => 'by admins and public submissions'],
                ['label' => 'Mills edited, last 90 days', 'value' => $stats->millsEditedSince(90), 'detail' => 'by admins and public submissions'],
                ['label' => 'Imports, last 12 months', 'value' => array_sum(array_slice($imports['imports'], -12))],
            ],
            'bySource' => $this->bySourceChart($stats),
            'byKind' => $selected ? null : $kindChart,
            'fields' => $fieldChart,
            'imports' => Chart::bar('imports', 'Imports per month')
                ->describe('Spreadsheet and ArcGIS imports started each month.')
                ->months(array_keys($imports['imports']))
                ->series('Imports', array_values($imports['imports']), 'series-1')
                ->whenEmpty('No imports in the last two years.'),
            'recentImports' => $stats->recentImports(),
            'recent' => $stats->recent(),
            'canSeeLog' => backpack_user()->can('activity_log.see'),
        ]);
    }

    public function compare()
    {
        $access = $this->authorizeStats();
        abort_unless($access->canCompareStates(), 403);
        Snapshots::ensureToday();

        $states = State::has('mills')->orderBy('name')->get(['id', 'name', 'abbreviation']);
        $completeness = MillStats::completenessByState();
        $ages = MillStats::dataAgeByState();
        $snapshots = Snapshots::since(now()->subDays(365));

        $complete = Chart::bar('complete-by-state', 'Mills with every key field')
            ->describe('Share of each state’s mills that have all '.count(MillStats::FIELDS).' key fields filled in.')
            ->labels($states->pluck('name')->all(), 'State')
            ->series('Mills with every key field', $states->map(fn ($s) => MillStats::percent(
                $completeness[$s->id]['complete'] ?? 0, $completeness[$s->id]['total'] ?? 0))->all(), 'series-1',
                $states->map(fn ($s) => $this->stateLink($access, $s))->all())
            ->horizontal()
            ->percent()
            ->fitBars()
            ->whenEmpty('No state has a mill with every key field yet.');

        $age = Chart::bar('age-by-state', 'Data age by state')
            ->describe('Share of each state’s mills by when they were last updated. Imports count as updates.')
            ->labels($states->pluck('name')->all(), 'State')
            ->horizontal()
            ->stacked()
            ->percent();
        foreach (MillStats::AGE_BUCKETS as $i => [$label]) {
            $age->series($label, $states->map(function ($s) use ($ages, $label) {
                $buckets = $ages[$s->id] ?? [];

                return MillStats::percent($buckets[$label] ?? 0, array_sum($buckets));
            })->all(), 'ord-'.($i + 1));
        }
        $age->fitBars(32);

        return $this->page('compare', 'Compare States', $access, [
            'states' => $states,
            'fields' => MillStats::FIELDS,
            'completeness' => $completeness,
            'complete' => $complete,
            'age' => $age,
            'trends' => $this->completenessTrends($states, $snapshots),
            'submissions' => SubmissionStats::byState(),
            'content' => $this->contentByState($states),
            'sectionCount' => count(StateHubController::SECTIONS),
            'stateLink' => fn (State $state) => $this->stateLink($access, $state),
        ]);
    }

    /**
     * Mill type and wood species names that may be the same thing written differently: candidates for stakeholders
     * to discuss merging. Read-only; merging also needs the import mappers updated.
     */
    public function similar()
    {
        $access = $this->authorizeStats();

        $lists = [];
        foreach (['types' => ['Mill types', SimilarNames::millTypes(), 'mill-type'],
            'species' => ['Wood species', SimilarNames::woodSpecies(), 'wood-species']] as $key => [$label, $names, $crud]) {
            $lists[$key] = [
                'label' => $label,
                'count' => $names->count(),
                'groups' => $names->groups(),
                'related' => $names->related(),
                'editLink' => backpack_user()->can($crud === 'mill-type' ? 'mill_types.edit' : 'wood_species.edit')
                    ? fn (int $id) => backpack_url("{$crud}/{$id}/edit")
                    : fn (int $id) => null,
            ];
        }

        return $this->page('similar', 'Similar Names', $access, ['lists' => $lists]);
    }

    /* ------------------------------------------------------------------------------------------------------------
     * Helpers
     * ---------------------------------------------------------------------------------------------------------- */

    private function authorizeStats(): StatsAccess
    {
        abort_unless(backpack_user()?->can('statistics.see'), 403);

        return StatsAccess::current();
    }

    private function page(string $view, string $title, StatsAccess $access, array $data)
    {
        return view("admin.statistics.{$view}", $data + [
            'title' => "{$title} :: Statistics",
            'heading' => $title,
            'breadcrumbs' => [
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                'Statistics' => backpack_url('statistics'),
                $title => false,
            ],
            'access' => $access,
            'current' => $view,
        ]);
    }

    /**
     * Link to a state's By State page, or null when this user can't open it.
     */
    private function stateLink(StatsAccess $access, ?State $state): ?string
    {
        if (! $state || ($access->isLockedToState() && $state->id !== $access->user->state_id)) {
            return null;
        }

        return backpack_url('statistics/state/'.$state->abbreviation);
    }

    private static function signed(int $n): string
    {
        return ($n > 0 ? '+' : ($n < 0 ? '−' : '±')).number_format(abs($n));
    }

    /**
     * A snapshot value from about $daysAgo days ago (the latest snapshot on or before then), or null.
     */
    private function snapshotValue(Collection $snapshots, int $daysAgo, callable $value): ?int
    {
        $cutoff = now()->subDays($daysAgo)->toDateString();
        $day = $snapshots->filter(fn ($s) => $s->captured_on->toDateString() <= $cutoff)->last();

        return $day ? (int) $value($day) : null;
    }

    private function overTime(string $id, string $title, Collection $snapshots, callable $value, string $series): Chart
    {
        $daily = Snapshots::daily($snapshots, $value);

        return Chart::line($id, $title)
            ->describe('Daily, from the statistics history.')
            ->days(array_keys($daily))
            ->series($series, array_values($daily), 'series-1')
            ->note(count($daily) < 2 ? 'History started '.(Snapshots::firstDay()?->format('M j, Y') ?? 'today').'. This chart fills in as days go by.' : null);
    }

    /**
     * Per-state tiles: mill count now, change over 30 days, and a 90-day sparkline.
     */
    private function stateTrends(Collection $states, Collection $snapshots, StatsAccess $access): array
    {
        $byState = $snapshots->groupBy('state_id');
        $since = now()->subDays(90)->toDateString();

        return $states->map(function (State $state) use ($byState, $since, $access) {
            $rows = ($byState[$state->id] ?? collect())->filter(fn ($s) => $s->captured_on->toDateString() >= $since)->values();
            $now = (int) ($rows->last()->metrics['mills'] ?? 0);
            $then = $this->snapshotValue($byState[$state->id] ?? collect(), 30, fn ($s) => $s->metrics['mills'] ?? 0);

            return [
                'state' => $state,
                'mills' => $now,
                'then' => $then,
                'change' => null === $then ? null : $now - $then,
                'link' => $this->stateLink($access, $state),
                'sparkline' => Chart::sparkline('trend-'.$state->abbreviation, $state->name.' mills, last 90 days')
                    ->days($rows->map(fn ($s) => $s->captured_on->toDateString())->all())
                    ->series('Mills', $rows->map(fn ($s) => $s->metrics['mills'] ?? 0)->all(), 'series-1'),
            ];
        })->all();
    }

    /**
     * Rows of states, columns of mill types, for the shaded table.
     */
    private function typesHeat(array $typesByState, array $types): array
    {
        $max = 0;
        $rows = [];
        foreach ($typesByState as $state => $counts) {
            $rows[$state] = array_map(fn ($type) => $counts[$type] ?? 0, $types);
            $max = max($max, ...$rows[$state]);
        }

        return ['columns' => $types, 'rows' => $rows, 'max' => $max];
    }

    private function countsChart(string $id, string $title, string $description, array $counts, string $heading, int $keep): Chart
    {
        $counts = MillStats::topWithOther($counts, $keep);

        return Chart::bar($id, $title)
            ->describe($description)
            ->labels(array_keys($counts), $heading)
            ->series('Mills', array_values($counts), 'series-1')
            ->horizontal()
            ->fitBars()
            ->whenEmpty('No mills have this information yet.');
    }

    private function addedChart(MillStats $stats): Chart
    {
        $added = $stats->addedPerMonth(24);

        return Chart::bar('added', 'Mills added per month')
            ->describe('Published mills by the month they were first added to this site.')
            ->months(array_keys($added['months']))
            ->series('Mills added', array_values($added['months']), 'series-1')
            ->note($added['unknown'] ? number_format($added['unknown']).' mills from before this site tracked when mills were added aren’t shown.' : null)
            ->whenEmpty('No mills were added in the last two years.');
    }

    private function dataAgeChart(string $id, array $buckets): Chart
    {
        return Chart::bar($id, 'Data age')
            ->describe('Mills by when they were last updated. Imports count as updates.')
            ->labels(array_keys($buckets), 'Last updated')
            ->series('Mills', array_values($buckets), 'series-1');
    }

    private function completenessChart(string $id, array $completeness): Chart
    {
        $fields = $completeness['fields'];

        return Chart::bar($id, 'Data completeness')
            ->describe('Share of mills with each field filled in.')
            ->labels(array_column($fields, 'label'), 'Field')
            ->series('Mills with this field', array_column($fields, 'percent'), 'series-1')
            ->horizontal()
            ->percent()
            ->fitBars()
            ->whenEmpty('No mills yet.');
    }

    /**
     * Contact fields over time, as a share of the state's mills.
     */
    private function completenessOverTime(Collection $snapshots): Chart
    {
        $chart = Chart::line('completeness-over-time', 'Completeness over time')
            ->describe('Share of mills with each contact field and a map location, daily.')
            ->percent();

        $dates = Snapshots::daily($snapshots, fn () => null);
        $chart->days(array_keys($dates));
        foreach (['email', 'phone', 'website', 'coordinates'] as $field) {
            $chart->series(MillStats::FIELDS[$field][0], array_values(Snapshots::daily($snapshots, fn (Collection $day) => MillStats::percent(
                $day->sum(fn ($s) => $s->metrics['fields'][$field] ?? 0),
                $day->sum(fn ($s) => $s->metrics['mills'] ?? 0),
            ))));
        }

        return $chart->note(count($dates) < 2 ? 'History started '.(Snapshots::firstDay()?->format('M j, Y') ?? 'today').'. This chart fills in as days go by.' : null);
    }

    /**
     * The state's most common mill types now, counted daily.
     */
    private function typesOverTime(Collection $snapshots, array $types): Chart
    {
        $chart = Chart::line('types-over-time', 'Mill types over time')
            ->describe('The state’s '.count($types).' most common mill types, daily.');
        $chart->days(array_keys(Snapshots::daily($snapshots, fn () => null)));
        foreach ($types as $type) {
            $chart->series($type, array_values(Snapshots::daily($snapshots, fn (Collection $day) => $day->sum(fn ($s) => $s->metrics['types'][$type] ?? 0))));
        }

        return $chart->note($snapshots->count() < 2 ? 'This chart fills in as days go by.' : null)
            ->whenEmpty('No mill types recorded yet.');
    }

    private function bySourceChart(ActivityStats $stats): Chart
    {
        $bySource = $stats->perMonthBySource();
        $chart = Chart::bar('by-source', 'Changes by source')
            ->describe('Logged changes per month: admin edits, approved public submissions, imports and background jobs.')
            ->months(array_keys(MillStats::fillMonths(now()->startOfMonth()->subMonths(11), 12, fn () => 0)))
            ->stacked()
            ->whenEmpty('Nothing has been logged yet.')
            ->note('The activity log started '.(ActivityStats::firstEntry()?->format('M j, Y') ?? 'with this release').'; earlier changes aren’t counted.');

        $slots = array_flip(array_values(ActivityStats::SOURCES));
        foreach ($bySource as $label => $months) {
            $chart->series($label, array_values($months), isset($slots[$label]) ? 'series-'.($slots[$label] + 1) : 'other');
        }

        return $chart;
    }

    /**
     * Payload for the county map: shading bins, county counts by FIPS, and mill points.
     */
    private function mapData(State $state, Collection $counties, MillStats $stats): ?array
    {
        $withFips = $counties->filter(fn ($c) => $c['fips']);
        if ($withFips->isEmpty()) {
            return null;
        }

        $max = (int) $withFips->max('mills');

        return [
            'stateFips' => substr($withFips->first()['fips'], 0, 2),
            'counties' => $withFips->mapWithKeys(fn ($c) => [$c['fips'] => ['name' => $c['name'], 'mills' => $c['mills']]])->all(),
            'bins' => self::bins($max),
            'points' => $stats->points(),
            'name' => $state->name,
        ];
    }

    /**
     * Up to four equal-width bins from 1 to $max (zero gets its own color).
     *
     * @return list<array{0: int, 1: int}>
     */
    public static function bins(int $max): array
    {
        if ($max < 1) {
            return [];
        }

        $count = min(4, $max);
        $width = (int) ceil($max / $count);
        $bins = [];
        for ($low = 1; $low <= $max; $low += $width) {
            $bins[] = [$low, min($max, $low + $width - 1)];
        }

        return $bins;
    }

    /**
     * The state's public page sections and when each was last changed.
     */
    private function stateSections(State $state): array
    {
        return array_map(function (array $section) use ($state) {
            $relation = $state->{$section['relation']}();
            $updated = $relation->max($relation->getRelated()->getTable().'.updated_at');

            return [
                'title' => $section['title'],
                'records' => $relation->count(),
                'updated' => $updated ? Carbon::parse($updated) : null,
            ];
        }, StateHubController::SECTIONS);
    }

    /**
     * For each state: how many page sections have content, and when any of them last changed.
     */
    private function contentByState(Collection $states): array
    {
        $result = [];
        foreach ($states as $state) {
            $sections = $this->stateSections($state);
            $result[$state->id] = [
                'filled' => count(array_filter($sections, fn ($s) => $s['records'] > 0)),
                'updated' => collect($sections)->pluck('updated')->filter()->max(),
            ];
        }

        return $result;
    }

    /**
     * Per-state tiles for Compare States: share of mills with every key field, now and over the last 90 days.
     */
    private function completenessTrends(Collection $states, Collection $snapshots): array
    {
        $byState = $snapshots->groupBy('state_id');
        $since = now()->subDays(90)->toDateString();

        return $states->map(function (State $state) use ($byState, $since) {
            $rows = ($byState[$state->id] ?? collect())->filter(fn ($s) => $s->captured_on->toDateString() >= $since)->values();
            $values = $rows->map(fn ($s) => MillStats::percent($s->metrics['complete'] ?? 0, $s->metrics['mills'] ?? 0));

            return [
                'state' => $state,
                'now' => $values->last(),
                'first' => $values->first(),
                'sparkline' => Chart::sparkline('complete-trend-'.$state->abbreviation, $state->name.' completeness, last 90 days')
                    ->days($rows->map(fn ($s) => $s->captured_on->toDateString())->all())
                    ->series('Mills with every key field', $values->all(), 'series-1')
                    ->percent(),
            ];
        })->all();
    }
}
