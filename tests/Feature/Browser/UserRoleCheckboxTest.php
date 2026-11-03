<?php

use App\Enums\UserRoles;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * user.js: one role per user, so ticking a role disables the other role checkboxes until it's unticked.
 * (The server enforces the same rule; see tests/Feature/Admin/UserRoleRulesTest.php.)
 */

beforeEach(function () {
    $this->editor = Role::findOrCreate(UserRoles::EDITOR->value, 'web');
    $this->agent = Role::findOrCreate(UserRoles::AGENT->value, 'web');

    $user = User::factory()->create();
    foreach (['users.edit', 'roles.edit', 'permissions.edit'] as $permission) {
        $user->givePermissionTo(Permission::findOrCreate($permission, 'web'));
    }
    $user->assignRole(Role::findOrCreate(UserRoles::SUPER->value, 'web'));
    // see UploadFieldContrastTest for why not actingAs($user, 'backpack')
    backpack_auth()->setUser($user);
});

function roleBox(Role $role): string
{
    return "input.primary_list[data-id=\"{$role->id}\"]";
}

test('ticking a role disables the others, and unticking it enables them again', function () {
    $page = visit('/admin/user/create')->wait(1);

    $disabled = fn (Role $role) => $page->script('document.querySelector('.json_encode(roleBox($role)).').disabled');

    expect($disabled($this->agent))->toBeFalse();

    $page->click(roleBox($this->editor))->wait(0.5);
    expect($disabled($this->agent))->toBeTrue()
        ->and($disabled($this->editor))->toBeFalse();

    $page->click(roleBox($this->editor))->wait(0.5);
    expect($disabled($this->agent))->toBeFalse();
})->group('browser', 'admin');
