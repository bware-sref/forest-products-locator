<?php

namespace App\Policies;

use App\Models\MillType;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class MillTypePolicy
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
    public function view(User $user, MillType $millType): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // maybe editors and/or state agents?
        return $user->isStateAgent();
    }

    /**
     * Determine whether the user can update the model.
     * 
     * Only admins and superadmins can update millTypes
     */
    public function update(User $user, MillType $millType): bool
    {
        // maybe editors?
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     * 
     */
    public function delete(User $user, MillType $millType): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, MillType $millType): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, MillType $millType): bool
    // {
    //     return false;
    // }
}
