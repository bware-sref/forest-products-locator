<?php

namespace App\Statistics;

use App\Models\State;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Which states a user may look at one at a time on the Statistics pages.
 *
 * State Agents see their own state plus region-wide totals; everyone else can pick any state with mills.
 * Putting several states side by side (Compare States) takes statistics.compare_states.
 */
final class StatsAccess
{
    public const string COMPARE_PERMISSION = 'statistics.compare_states';

    public function __construct(public readonly User $user) {}

    public static function current(): self
    {
        return new self(backpack_user());
    }

    public function isLockedToState(): bool
    {
        return $this->user->isStateAgent();
    }

    public function canCompareStates(): bool
    {
        return $this->user->can(self::COMPARE_PERMISSION);
    }

    /**
     * @return Collection<int, State> ordered by name
     */
    public function states(): Collection
    {
        $query = State::query()->select(['id', 'name', 'abbreviation'])->orderBy('name');

        return $this->isLockedToState()
            ? $query->whereKey($this->user->state_id)->get()
            : $query->has('mills')->get();
    }

    /**
     * The state named in the URL (abbreviation or id), or a sensible default when none is: the user's own state,
     * else the first one alphabetically. Null when the user may not see the requested state or there are none.
     */
    public function resolveState(?string $key): ?State
    {
        $states = $this->states();

        if (null === $key || '' === $key) {
            return $states->firstWhere('id', $this->user->state_id) ?? $states->first();
        }

        return $states->first(fn (State $state) => strcasecmp($state->abbreviation, $key) === 0
            || (ctype_digit($key) && $state->id === (int) $key));
    }

    /**
     * For the optional state filter on region-wide pages: null means all states.
     * A state the user may not see is treated as no filter, never as someone else's data.
     */
    public function filterState(?string $key): ?State
    {
        if (null === $key || '' === $key) {
            return null;
        }

        return $this->resolveState($key);
    }
}
