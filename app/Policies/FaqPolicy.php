<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Faq;
use Illuminate\Auth\Access\Response;

class FaqPolicy
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
    public function view(User $user, Faq $faq): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Faq $faq): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Faq $faq): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, Faq $faq): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, Faq $faq): bool
    // {
    //     return false;
    // }
}
