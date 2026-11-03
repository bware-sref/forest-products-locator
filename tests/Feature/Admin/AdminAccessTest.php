<?php

use App\Enums\UserRoles;
use App\Http\Middleware\CheckIfAdmin;
use App\Models\User;
use App\Rules\KeepsLastSuperadmin;
use Spatie\Permission\Models\Role;

/**
 * Who may use the admin panel (CheckIfAdmin), and keeping at least one Superadmin (KeepsLastSuperadmin).
 *
 * One admin request per test: Backpack's CRUD panel holds per-request state.
 */

describe('users with no role and no permissions', function () {
    test('are logged out of the admin panel and told why', function () {
        backpack_auth()->setUser(User::factory()->create());

        $this->get('/admin/dashboard')
            ->assertRedirect(backpack_url('login'))
            ->assertSessionHasErrors(['email' => CheckIfAdmin::NO_ACCESS_MESSAGE]);

        expect(backpack_auth()->check())->toBeFalse();
    });

    test('get a 403 on JSON requests', function () {
        backpack_auth()->setUser(User::factory()->create());

        $this->getJson('/admin/dashboard')->assertForbidden();
    });

    test('a role alone is enough', function () {
        loginAdminUser([], UserRoles::EDITOR);

        $this->get('/admin/dashboard')->assertOk();
    });

    test('a direct permission alone is enough', function () {
        loginAdminUser(['mills.see']);

        $this->get('/admin/dashboard')->assertOk();
    });
});

describe('the last Superadmin', function () {
    beforeEach(function () {
        $this->superRole = Role::findOrCreate(UserRoles::SUPER->value, 'web');
        $this->editorRole = Role::findOrCreate(UserRoles::EDITOR->value, 'web');
        $this->super = loginAdminUser(['users.edit', 'roles.edit', 'permissions.edit'], UserRoles::SUPER);
    });

    function editSelf(User $user, array $roles): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
            'roles' => $roles,
            'permissions' => [],
            'agent_role_name' => UserRoles::AGENT->value,
            'is_agent' => 'false',
        ];
    }

    test('can\'t untick every role', function () {
        $this->put("/admin/user/{$this->super->id}", editSelf($this->super, []))
            ->assertSessionHasErrors(['roles' => KeepsLastSuperadmin::MESSAGE]);

        expect($this->super->fresh()->hasRole(UserRoles::SUPER))->toBeTrue();
    });

    test('can\'t switch to another role', function () {
        $this->put("/admin/user/{$this->super->id}", editSelf($this->super, [$this->editorRole->id]))
            ->assertSessionHasErrors('roles');

        expect($this->super->fresh()->hasRole(UserRoles::SUPER))->toBeTrue();
    });

    test('can still save their account with the role kept', function () {
        $this->put("/admin/user/{$this->super->id}", editSelf($this->super, [$this->superRole->id]))
            ->assertSessionHasNoErrors();
    });

    test('can give up the role once another user has it', function () {
        User::factory()->create()->assignRole($this->superRole);

        $this->put("/admin/user/{$this->super->id}", editSelf($this->super, [$this->editorRole->id]))
            ->assertSessionHasNoErrors();

        expect($this->super->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
    });

    test('can\'t be deleted', function () {
        $this->deleteJson("/admin/user/{$this->super->id}")->assertStatus(422);

        expect(User::find($this->super->id))->not->toBeNull();
    });

    test('another Superadmin can be deleted while one remains', function () {
        $other = User::factory()->create();
        $other->assignRole($this->superRole);

        $this->deleteJson("/admin/user/{$other->id}")->assertOk();

        expect(User::find($other->id))->toBeNull();
    });
});
