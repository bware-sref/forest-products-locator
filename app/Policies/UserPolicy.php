<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    protected array $adminAbilities = [];

    /**
     * IMPORTANT: before() will not be invoked if this policy class does not have a method with a name matching the
     * $ability being checked.
     */
    public function before(User $user, string $ability): bool|null
    {
        /**
         * Not even superusers can delete themselves.
         */
        if ('delete' !== $ability && $user->isSuper()) {
            return true;
        }

        /**
         * returning null causes before() to fall through to the method whose name matches the $ability being checked.
         */ 
        return null;
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
    public function view(User $user, User $model): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     * 
     * Users cannot delete themselves.
     * Not even if they're an admin or superadmin
     */
    public function delete(User $user, User $model): bool
    {
        return $user->id !== $model->id && ($user->isAdmin() || $user->isSuper());
    }

    /**
     * Determine whether the user can restore the model.
     * We're not using soft deletes.
     */
    // public function restore(User $user, User $model): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     * We're not using soft deletes.
     */
    // public function forceDelete(User $user, User $model): bool
    // {
    //     return false;
    // }
}
