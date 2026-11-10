<?php

namespace App\Jobs\Concerns;

use App\Enums\UserRoles;
use App\Models\State;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Who reviews a public submission (new business or mill edit):
 *  1. every Superadmin and Administrator, always;
 *  2. the State Agent users for the mill's state;
 *  3. only if that state has no State Agents yet, its state contacts, as a fallback.
 */
trait ResolvesReviewRecipients
{
    /**
     * @return list<array{email: string, name: ?string}> the shape Mail::to() expects
     */
    protected function reviewRecipientsFor(?State $state): array
    {
        $siteReviewers = $this->usersWithRole([UserRoles::SUPER, UserRoles::ADMIN])->get();

        $stateReviewers = collect();
        if ($state) {
            $stateReviewers = $this->usersWithRole([UserRoles::AGENT])
                ->where('state_id', $state->id)
                ->get();

            if ($stateReviewers->isEmpty()) {
                $stateReviewers = $state->stateContacts()->select(['email', 'name'])->get();
            }
        }

        return $siteReviewers->concat($stateReviewers)
            ->map(fn($person) => ['email' => trim((string) $person->email), 'name' => $person->name])
            ->filter(fn(array $person) => $person['email'] !== '')
            // someone can be both, e.g., a Superadmin who is also listed as a state contact
            ->unique(fn(array $person) => strtolower($person['email']))
            ->values()
            ->all();
    }

    /**
     * Filters on role names directly: Spatie's User::role() throws if a role hasn't been created yet.
     *
     * @param  list<UserRoles>  $roles
     */
    private function usersWithRole(array $roles): Builder
    {
        return User::query()
            ->select(['users.id', 'users.email', 'users.name', 'users.state_id'])
            ->whereHas('roles', fn(Builder $query) => $query->whereIn('name', array_map(fn(UserRoles $role) => $role->value, $roles)));
    }

    protected function getSiteReviewers(): array
    {
        return $this->usersWithRole([UserRoles::SUPER, UserRoles::ADMIN])
            ->get(['email', 'name'])
            ->toArray();
    }
}
