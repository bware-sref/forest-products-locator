<?php

namespace App\Statistics;

use App\Enums\PublicationStatus;
use App\Models\Mill;
use App\Models\MillEdit;
use App\Models\State;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Public submissions (mill_edits): new-mill suggestions and corrections to existing mills.
 *
 * A submission's state is its mill's state, or for a new mill the state it was submitted with. Submissions are
 * loaded into memory: there are few of them, and their state and changed fields live in JSON.
 */
final class SubmissionStats
{
    /**
     * Pending-age buckets: [label, younger than this many days]; the last catches the rest.
     */
    public const array PENDING_BUCKETS = [
        ['Under a week', 7],
        ['1–4 weeks', 30],
        ['1–3 months', 90],
        ['Over 3 months', null],
    ];

    /** @var Collection<int, object>|null */
    private ?Collection $rows = null;

    public function __construct(public readonly ?int $stateId = null) {}

    public static function for(?State $state): self
    {
        return new self($state?->id);
    }

    /**
     * Every submission in scope: id, status, is_new, state_id, mill_name, created_at, reviewed_at, fields, review_hash.
     *
     * @return Collection<int, object>
     */
    public function rows(): Collection
    {
        return $this->rows ??= self::allRows()
            ->when($this->stateId, fn (Collection $rows) => $rows->where('state_id', $this->stateId))
            ->values();
    }

    /**
     * @return Collection<int, object>
     */
    public static function allRows(): Collection
    {
        $edits = MillEdit::query()->orderBy('id')->get();
        $mills = Mill::withoutGlobalScopes()
            ->whereKey($edits->pluck('mill_id')->filter()->unique())
            ->get(['id', 'state_id', 'mill_name'])
            ->keyBy('id');

        return $edits->map(function (MillEdit $edit) use ($mills) {
            $mill = $edit->mill_id ? $mills->get($edit->mill_id) : null;
            $changes = $edit->proposed_changes['changes'] ?? [];
            $diff = $edit->proposed_changes['diff'] ?? [];
            $status = $edit->status instanceof PublicationStatus ? $edit->status : PublicationStatus::tryFrom((string) $edit->status);
            $reviewedAt = PublicationStatus::Pending === $status ? null : ($edit->reviewed_at ?? $edit->updated_at);

            return (object) [
                'id' => $edit->id,
                'status' => $status,
                // a new-mill submission has no diff; approving it fills in mill_id, so mill_id can't tell
                'is_new' => empty($diff),
                'state_id' => (int) ($mill->state_id ?? $changes['state_id'] ?? 0) ?: null,
                'mill_name' => $mill->mill_name ?? ($changes['mill_name'] ?? null),
                'created_at' => $edit->created_at,
                'reviewed_at' => $reviewedAt ? Carbon::parse($reviewedAt) : null,
                'fields' => array_keys($diff),
                'review_hash' => $edit->review_hash,
            ];
        });
    }

    /**
     * @return array{total: int, pending: int, approved: int, rejected: int, new: int, edits: int}
     */
    public function totals(): array
    {
        $rows = $this->rows();

        return [
            'total' => $rows->count(),
            'pending' => $rows->where('status', PublicationStatus::Pending)->count(),
            'approved' => $rows->where('status', PublicationStatus::Approved)->count(),
            'rejected' => $rows->where('status', PublicationStatus::Rejected)->count(),
            'new' => $rows->where('is_new', true)->count(),
            'edits' => $rows->where('is_new', false)->count(),
        ];
    }

    /**
     * Submissions received per month, split into new mills and corrections.
     *
     * @return array{new: array<string, int>, edits: array<string, int>}
     */
    public function receivedPerMonth(int $months = 24): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $byMonth = fn (bool $isNew) => MillStats::fillMonths($start, $months, fn ($key) => $this->rows()
            ->where('is_new', $isNew)
            ->filter(fn ($row) => $row->created_at?->format('Y-m') === $key)
            ->count());

        return ['new' => $byMonth(true), 'edits' => $byMonth(false)];
    }

    /**
     * Reviews per month by outcome.
     *
     * @return array{approved: array<string, int>, rejected: array<string, int>}
     */
    public function reviewedPerMonth(int $months = 24): array
    {
        $start = now()->startOfMonth()->subMonths($months - 1);
        $byMonth = fn (PublicationStatus $status) => MillStats::fillMonths($start, $months, fn ($key) => $this->rows()
            ->where('status', $status)
            ->filter(fn ($row) => $row->reviewed_at?->format('Y-m') === $key)
            ->count());

        return ['approved' => $byMonth(PublicationStatus::Approved), 'rejected' => $byMonth(PublicationStatus::Rejected)];
    }

    /**
     * Pending submissions by how long they've waited.
     *
     * @return array<string, int>
     */
    public function pendingAges(): array
    {
        $pending = $this->rows()->where('status', PublicationStatus::Pending);
        $buckets = [];
        $previous = null;
        foreach (self::PENDING_BUCKETS as [$label, $days]) {
            $buckets[$label] = $pending->filter(function ($row) use ($days, $previous) {
                $age = $row->created_at?->diffInDays(now()) ?? 0;

                return (null === $previous || $age >= $previous) && (null === $days || $age < $days);
            })->count();
            $previous = $days;
        }

        return $buckets;
    }

    /**
     * Median and 90th-percentile days from submission to review, over reviews in the last $days days.
     *
     * @return array{reviews: int, median: ?float, p90: ?float}
     */
    public function reviewTimes(int $days = 365): array
    {
        $since = now()->subDays($days);
        $durations = $this->rows()
            ->filter(fn ($row) => $row->reviewed_at && $row->created_at && $row->reviewed_at->gte($since))
            ->map(fn ($row) => max(0, $row->created_at->diffInHours($row->reviewed_at) / 24))
            ->sort()
            ->values();

        return [
            'reviews' => $durations->count(),
            'median' => self::quantile($durations, 0.5),
            'p90' => self::quantile($durations, 0.9),
        ];
    }

    /**
     * Fields the public changes most often in corrections (from each submission's diff).
     *
     * @return array<string, int> field label => submissions, most common first
     */
    public function fieldsChanged(): array
    {
        return $this->rows()
            ->where('is_new', false)
            ->flatMap(fn ($row) => $row->fields)
            ->countBy()
            ->sortDesc()
            ->mapWithKeys(fn ($count, $field) => [self::fieldLabel($field) => $count])
            ->all();
    }

    /**
     * Pending submissions, oldest first.
     *
     * @return Collection<int, object>
     */
    public function oldestPending(int $limit = 10): Collection
    {
        return $this->rows()
            ->where('status', PublicationStatus::Pending)
            ->sortBy('created_at')
            ->take($limit)
            ->values();
    }

    /**
     * Per-state pending count, oldest wait, and review times (Compare States only).
     *
     * @return array<int, array{pending: int, oldest_days: ?int, reviews: int, median: ?float, approved: int, rejected: int}>
     */
    public static function byState(): array
    {
        $since = now()->subDays(365);

        return self::allRows()
            ->filter(fn ($row) => $row->state_id)
            ->groupBy('state_id')
            ->map(function (Collection $rows) use ($since) {
                $pending = $rows->where('status', PublicationStatus::Pending);
                $durations = $rows
                    ->filter(fn ($row) => $row->reviewed_at && $row->created_at && $row->reviewed_at->gte($since))
                    ->map(fn ($row) => max(0, $row->created_at->diffInHours($row->reviewed_at) / 24))
                    ->sort()
                    ->values();
                $oldest = $pending->min('created_at');

                return [
                    'pending' => $pending->count(),
                    'oldest_days' => $oldest ? (int) $oldest->diffInDays(now()) : null,
                    'reviews' => $durations->count(),
                    'median' => self::quantile($durations, 0.5),
                    'approved' => $rows->where('status', PublicationStatus::Approved)->count(),
                    'rejected' => $rows->where('status', PublicationStatus::Rejected)->count(),
                ];
            })
            ->all();
    }

    /**
     * @param  Collection<int, float>  $sorted
     */
    private static function quantile(Collection $sorted, float $q): ?float
    {
        if ($sorted->isEmpty()) {
            return null;
        }

        $position = ($sorted->count() - 1) * $q;
        $low = (int) floor($position);
        $high = (int) ceil($position);
        $value = $sorted[$low] + ($sorted[$high] - $sorted[$low]) * ($position - $low);

        return round($value, 1);
    }

    public static function fieldLabel(string $field): string
    {
        return match ($field) {
            'web_site' => 'Website',
            'telephone' => 'Phone',
            'telephone_2' => 'Second phone',
            'email_2' => 'Second email',
            'mill_types' => 'Mill types',
            'wood_species' => 'Wood species',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }
}
