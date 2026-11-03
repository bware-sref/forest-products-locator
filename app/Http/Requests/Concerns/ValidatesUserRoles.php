<?php

namespace App\Http\Requests\Concerns;

use App\Enums\UserRoles;
use App\Models\User;
use App\Rules\KeepsLastSuperadmin;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

/**
 * Role rules shared by StoreUserRequest and UpdateUserRequest: one role per user, and a state for State Agents.
 *
 * Decided from the submitted roles, not the is_agent field user.js sets, so the rule holds even when the script
 * doesn't run. user.js still shows and hides the State field.
 */
trait ValidatesUserRoles
{
    /**
     * One role per user: Spatie allows several, but this app doesn't need them, and combinations (e.g., State
     * Agent plus Administrator) blur who is limited to a state. No role at all is still allowed.
     */
    protected function rolesRules(): array
    {
        return ['nullable', 'array', 'max:1'];
    }

    /**
     * Update only: the last Superadmin can't lose that role (KeepsLastSuperadmin).
     */
    protected function keepsLastSuperadminRule(): KeepsLastSuperadmin
    {
        $userId = $this->get('id') ?? $this->route('id');

        return new KeepsLastSuperadmin($userId ? User::find($userId) : null, $this->exists('roles'));
    }

    protected function stateIdRules(): array
    {
        return [
            // non-agents don't keep a state: state_id is left out of the save entirely
            Rule::excludeUnless($this->submitsAgentRole()),
            'required',
            'numeric',
            'exists:states,id',
        ];
    }

    /**
     * The roles checklist posts role ids as roles[] (and an empty roles value when nothing is ticked).
     */
    public function submitsAgentRole(): bool
    {
        $agentRoleId = Role::where('name', UserRoles::AGENT->value)->value('id');

        if (! $agentRoleId) {
            return false;
        }

        return collect((array) $this->input('roles', []))
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->contains((int) $agentRoleId);
    }

    protected function roleMessages(): array
    {
        return [
            'state_id.required' => 'State is required for users having the "'.UserRoles::AGENT->value.'" role.',
            'roles.max' => 'A user can have only one role.',
        ];
    }
}
