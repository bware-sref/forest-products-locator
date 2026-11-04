<?php

namespace App\Statistics;

use App\Models\Mill;
use App\Models\State;
use App\Models\StatisticsSnapshot;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Daily per-state snapshots of mill statistics, the history behind the "over time" charts.
 *
 * The mills table only describes each mill as it is now, so trends (mill counts, completeness, mill types) have to
 * be recorded as they happen. History starts the day this was deployed; there's nothing to backfill from.
 */
final class Snapshots
{
    /**
     * Record today's numbers for every state that has mills, or had them in the last snapshot (so a state whose
     * mills were all removed records a zero instead of vanishing). Replaces today's rows if they exist.
     *
     * @return int states recorded
     */
    public static function capture(?Carbon $day = null): int
    {
        $day = ($day ?? now())->toDateString();

        $completeness = MillStats::completenessByState();
        $types = self::typesByStateId();

        $lastDay = StatisticsSnapshot::where('captured_on', '<', $day)->max('captured_on');
        $stateIds = collect(array_keys($completeness))
            ->merge($lastDay ? StatisticsSnapshot::whereDate('captured_on', $lastDay)->pluck('state_id') : [])
            ->unique();
        // only states that exist (mills.state_id has no foreign key)
        $stateIds = State::whereKey($stateIds)->pluck('id');

        $now = now();
        $rows = $stateIds->map(function (int $stateId) use ($day, $completeness, $types, $now) {
            $stats = $completeness[$stateId] ?? null;

            return [
                'captured_on' => $day,
                'state_id' => $stateId,
                'metrics' => json_encode([
                    'mills' => $stats['total'] ?? 0,
                    'complete' => $stats['complete'] ?? 0,
                    'fields' => collect($stats['fields'] ?? [])->map(fn ($f) => $f['count'])->all(),
                    'types' => $types[$stateId] ?? [],
                ]),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        })->values()->all();

        StatisticsSnapshot::upsert($rows, ['captured_on', 'state_id'], ['metrics', 'updated_at']);

        return count($rows);
    }

    /**
     * Take today's snapshot if nobody has yet, so history keeps building even without the scheduler.
     */
    public static function ensureToday(): void
    {
        if (! StatisticsSnapshot::whereDate('captured_on', now()->toDateString())->exists()) {
            self::capture();
        }
    }

    /**
     * @return array<int, array<string, int>> state id => (type name => mills)
     */
    private static function typesByStateId(): array
    {
        $rows = Mill::query()->toBase()
            ->join('mill_mill_type', 'mill_mill_type.mill_id', '=', 'mills.id')
            ->join('mill_types', 'mill_types.id', '=', 'mill_mill_type.mill_type_id')
            ->whereNotNull('mills.state_id')
            ->selectRaw('mills.state_id, mill_types.name AS type, COUNT(*) AS mills')
            ->groupBy('mills.state_id', 'mill_types.name')
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[(int) $row->state_id][$row->type] = (int) $row->mills;
        }

        return $result;
    }

    /**
     * Snapshots since $since, oldest first, for one state or (null) all states.
     *
     * @return Collection<int, StatisticsSnapshot>
     */
    public static function since(Carbon $since, ?int $stateId = null): Collection
    {
        return StatisticsSnapshot::query()
            ->where('captured_on', '>=', $since->toDateString())
            ->when($stateId, fn ($q) => $q->where('state_id', $stateId))
            ->orderBy('captured_on')
            ->get(['captured_on', 'state_id', 'metrics']);
    }

    /**
     * One value per day: $value receives the day's snapshots (one per state, or one when filtered to a state).
     *
     * @param  Collection<int, StatisticsSnapshot>  $snapshots
     * @return array<string, mixed> 'Y-m-d' => value
     */
    public static function daily(Collection $snapshots, callable $value): array
    {
        return $snapshots
            ->groupBy(fn (StatisticsSnapshot $s) => $s->captured_on->toDateString())
            ->map(fn (Collection $day) => $value($day))
            ->all();
    }

    /**
     * The date history starts, or null before the first snapshot.
     */
    public static function firstDay(): ?Carbon
    {
        $first = StatisticsSnapshot::min('captured_on');

        return $first ? Carbon::parse($first) : null;
    }
}
