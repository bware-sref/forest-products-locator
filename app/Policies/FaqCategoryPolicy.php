<?php

namespace App\Policies;

use App\Models\User;
use App\Models\FaqCategory;
use Illuminate\Auth\Access\Response;

class FaqCategoryPolicy
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
    public function view(User $user, FaqCategory $faqCategory): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // maybe editors?
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, FaqCategory $faqCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, FaqCategory $faqCategory): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    // public function restore(User $user, FaqCategory $faqCategory): bool
    // {
    //     return false;
    // }

    /**
     * Determine whether the user can permanently delete the model.
     */
    // public function forceDelete(User $user, FaqCategory $faqCategory): bool
    // {
    //     return false;
    // }
}
