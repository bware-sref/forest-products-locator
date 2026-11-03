<?php

use App\Enums\UserRoles;
use App\Http\Middleware\BlockIncompleteStateAgents;
use App\Models\Mill;
use App\Models\State;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * Role rules for admin users:
 *  - one role per user: the admin user form rejects more than one (ValidatesUserRoles);
 *  - the form requires a state whenever the State Agent role is ticked (ValidatesUserRoles), whether or not
 *    user.js ran;
 *  - anyone who has the role without one anyway (seeders, tinker, a deleted state) is kept out of the admin panel
 *    (BlockIncompleteStateAgents), because nothing else would limit them to a state.
 *
 * One admin request per test: Backpack's CRUD panel holds per-request state.
 */

beforeEach(function () {
    $this->state = State::factory()->create();
    // the user form's State select only offers states with mills, and Backpack rejects values outside its options
    Mill::factory()->for($this->state)->create();
    $this->agentRole = Role::findOrCreate(UserRoles::AGENT->value, 'web');
    $this->editorRole = Role::findOrCreate(UserRoles::EDITOR->value, 'web');
});

function loginUserManager(): User
{
    // a Superadmin: anyone else can only edit their own account (UserCrudController::restrictToOwnRecordUnlessSuper())
    return loginAdminUser(['users.edit', 'roles.edit', 'permissions.edit'], UserRoles::SUPER);
}

/**
 * What the admin user form posts. is_agent is what user.js would set; 'false' mimics the script not running.
 */
function userForm(array $overrides = []): array
{
    return [
        'name' => 'New Person',
        'email' => 'new.person@example.com',
        'password' => 'a-long-enough-password',
        'password_confirmation' => 'a-long-enough-password',
        'roles' => [],
        'permissions' => [],
        'agent_role_name' => UserRoles::AGENT->value,
        'is_agent' => 'false',
        ...$overrides,
    ];
}

describe('creating a user', function () {
    test('the State Agent role without a state is refused, even if user.js didn\'t flag it', function () {
        loginUserManager();

        $this->post('/admin/user', userForm(['roles' => [$this->agentRole->id]]))
            ->assertSessionHasErrors('state_id');

        expect(User::where('email', 'new.person@example.com')->exists())->toBeFalse();
    });

    test('the State Agent role with a state that doesn\'t exist is refused', function () {
        loginUserManager();

        $this->post('/admin/user', userForm(['roles' => [$this->agentRole->id], 'state_id' => 999999]))
            ->assertSessionHasErrors('state_id');
    });

    test('the State Agent role with a state is saved', function () {
        loginUserManager();

        $this->post('/admin/user', userForm(['roles' => [$this->agentRole->id], 'state_id' => $this->state->id]))
            ->assertSessionHasNoErrors();

        $user = User::where('email', 'new.person@example.com')->firstOrFail();
        expect($user->state_id)->toBe($this->state->id)
            ->and($user->hasRole(UserRoles::AGENT))->toBeTrue();
    });

    test('other roles don\'t need a state, and don\'t keep one', function () {
        loginUserManager();

        $this->post('/admin/user', userForm(['roles' => [$this->editorRole->id], 'state_id' => $this->state->id]))
            ->assertSessionHasNoErrors();

        expect(User::where('email', 'new.person@example.com')->firstOrFail()->state_id)->toBeNull();
    });
});

describe('updating a user', function () {
    beforeEach(function () {
        $this->person = User::factory()->create(['email' => 'existing@example.com']);
    });

    function updateForm(User $person, array $overrides = []): array
    {
        return userForm([
            'id' => $person->id,
            'name' => $person->name,
            'email' => $person->email,
            'password' => '',
            'password_confirmation' => '',
            ...$overrides,
        ]);
    }

    test('adding the State Agent role without a state is refused', function () {
        loginUserManager();

        $this->put("/admin/user/{$this->person->id}", updateForm($this->person, ['roles' => [$this->agentRole->id]]))
            ->assertSessionHasErrors('state_id');

        expect($this->person->fresh()->hasRole(UserRoles::AGENT))->toBeFalse();
    });

    test('adding the State Agent role with a state is saved', function () {
        loginUserManager();

        $this->put("/admin/user/{$this->person->id}", updateForm($this->person, [
            'roles' => [$this->agentRole->id],
            'state_id' => $this->state->id,
        ]))->assertSessionHasNoErrors();

        $person = $this->person->fresh();
        expect($person->state_id)->toBe($this->state->id)
            ->and($person->hasRole(UserRoles::AGENT))->toBeTrue();
    });

    test('an existing State Agent can\'t have their state removed', function () {
        $this->person->update(['state_id' => $this->state->id]);
        $this->person->assignRole($this->agentRole);
        loginUserManager();

        $this->put("/admin/user/{$this->person->id}", updateForm($this->person, [
            'roles' => [$this->agentRole->id],
            'state_id' => '',
        ]))->assertSessionHasErrors('state_id');

        expect($this->person->fresh()->state_id)->toBe($this->state->id);
    });
});

describe('State Agents without a valid state', function () {
    test('are logged out of the admin panel and told why', function (string $path) {
        loginAdminUser(['mills.edit', 'state_contacts.edit', 'statistics.see'], UserRoles::AGENT, null);

        $this->get($path)
            ->assertRedirect(backpack_url('login'))
            ->assertSessionHasErrors(['email' => BlockIncompleteStateAgents::MESSAGE]);

        expect(backpack_auth()->check())->toBeFalse();
    })->with([
        'dashboard' => '/admin/dashboard',
        'mills' => '/admin/mill',
        'state contacts' => '/admin/state-contact',
        'state hub' => '/admin/state-hub',
    ]);

    test('are kept out when their state no longer exists', function () {
        loginAdminUser(['mills.edit'], UserRoles::AGENT, 999999);

        $this->get('/admin/mill')->assertRedirect(backpack_url('login'));
    });

    test('get a 403 on JSON requests, such as list data', function () {
        loginAdminUser(['mills.edit'], UserRoles::AGENT, null);

        $this->postJson('/admin/mill/search', ['draw' => 1, 'start' => 0, 'length' => 10])->assertForbidden();
    });

    test('a State Agent with a state still gets in', function () {
        loginStateAgent($this->state->id, ['mills.edit']);

        $this->get('/admin/mill')->assertOk();
    });

    test('Superadmins and Administrators who also have the role aren\'t blocked', function (UserRoles $role) {
        $user = loginAdminUser(['mills.edit'], UserRoles::AGENT, null);
        // a second role can only come from a raw attach now (User::assignRole() refuses it); the exemption is the safety net
        $user->roles()->attach(Role::findOrCreate($role->value, 'web')->id);

        $this->get('/admin/mill')->assertOk();
    })->with([
        'Superadmin' => UserRoles::SUPER,
        'Administrator' => UserRoles::ADMIN,
    ]);

    test('users without the role don\'t need a state', function () {
        loginAdminUser(['mills.edit']);

        $this->get('/admin/mill')->assertOk();
    });
});

describe('one role per user', function () {
    test('creating a user with two roles is refused', function () {
        loginUserManager();

        $this->post('/admin/user', userForm([
            'roles' => [$this->editorRole->id, $this->agentRole->id],
            'state_id' => $this->state->id,
        ]))->assertSessionHasErrors(['roles' => 'A user can have only one role.']);

        expect(User::where('email', 'new.person@example.com')->exists())->toBeFalse();
    });

    test('giving an existing user a second role is refused', function () {
        $person = User::factory()->create();
        $person->assignRole($this->editorRole);
        loginUserManager();

        $this->put("/admin/user/{$person->id}", updateForm($person, [
            'roles' => [$this->editorRole->id, $this->agentRole->id],
            'state_id' => $this->state->id,
        ]))->assertSessionHasErrors('roles');

        expect($person->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
    });

    test('switching a user to a different role is fine', function () {
        $person = User::factory()->create();
        $person->assignRole($this->editorRole);
        loginUserManager();

        $this->put("/admin/user/{$person->id}", updateForm($person, [
            'roles' => [$this->agentRole->id],
            'state_id' => $this->state->id,
        ]))->assertSessionHasNoErrors();

        expect($person->fresh()->getRoleNames()->all())->toBe([UserRoles::AGENT->value]);
    });

    test('a user with no role is still allowed', function () {
        loginUserManager();

        $this->post('/admin/user', userForm(['roles' => '']))->assertSessionHasNoErrors();

        expect(User::where('email', 'new.person@example.com')->exists())->toBeTrue();
    });
});
