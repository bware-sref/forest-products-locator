<?php

namespace App\Policies;

use App\Models\County;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CountyPolicy
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
    public function view(User $user, County $county): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // either isAdmin() or isAgent()
        // return $user->isAdmin();
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, County $county): bool
    {
        // either isAdmin() or isAgent() and county in same state
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, County $county): bool
    {
        // either isAdmin() or isAgent() and county in same state
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, County $county): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, County $county): bool
    // {
    //     return false;
    // }
}
