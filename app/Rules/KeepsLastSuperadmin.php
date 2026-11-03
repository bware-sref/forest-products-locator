<?php

namespace App\Rules;

use App\Enums\UserRoles;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Spatie\Permission\Models\Role;

/**
 * The last Superadmin can't lose that role, or nobody could edit other users
 * (UserCrudController::restrictToOwnRecordUnlessSuper()). Give someone else the role first.
 */
class KeepsLastSuperadmin implements ValidationRule
{
    public const string MESSAGE = 'This is the only Superadmin. Give another user the Superadmin role before changing this one.';

    /**
     * Run even when the value is empty: unticking every role posts an empty roles value, the case to catch.
     */
    public bool $implicit = true;

    /**
     * @param  bool  $rolesSubmitted  false when the form has no roles field (the editor can't edit roles), so nothing changes
     */
    public function __construct(private ?User $user, private bool $rolesSubmitted) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $this->rolesSubmitted || ! $this->user?->isLastSuperadmin()) {
            return;
        }

        $superRoleId = (int) Role::where('name', UserRoles::SUPER->value)->value('id');
        $keepsSuper = collect((array) $value)->map(fn ($id) => (int) $id)->contains($superRoleId);

        if (! $keepsSuper) {
            $fail(self::MESSAGE);
        }
    }
}
