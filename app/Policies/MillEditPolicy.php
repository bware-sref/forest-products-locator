<?php

namespace App\Policies;

use App\Models\MillEdit;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MillEditPolicy
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
        // either admin or state agent
        return $user->isStateAgent();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MillEdit $millEdit): bool
    {
        // either admin or state agent
        // we probably also need to check what state the mill is in.
        // the expression below seems like it would do the trick, but we should probably make it a method on User or Agent.
        // I guess on User for now because Agent might go away.
        // $user->agent->state_id === $millEdit->mill->state_id
        return $user->isStateAgent();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // either admin or state agent
        return $user->isStateAgent();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MillEdit $millEdit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MillEdit $millEdit): bool
    {
        return false;
    }

    public function approve(User $user, MillEdit $millEdit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, MillEdit $millEdit): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, MillEdit $millEdit): bool
    // {
    //     return false;
    // }
}
