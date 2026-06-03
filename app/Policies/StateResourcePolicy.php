<?php

namespace App\Policies;

use App\Models\StateResource;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class StateResourcePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return ($user->isSuper() || $user->isAdmin()) ? true : null;
    }

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StateResource $stateResource): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isStateAgent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, StateResource $stateResource): bool
    {
        return $user->isAgentFor($stateResource);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StateResource $stateResource): bool
    {
        return $user->isAgentFor($stateResource);
        // return $user->state_id === $stateResource->state_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, StateResource $stateResource): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, StateResource $stateResource): bool
    // {
    //     return false;
    // }
}
