<?php

use App\Enums\UserRoles;
use App\Models\User;
use Spatie\Permission\Models\Role;

/**
 * User::assignRole(): one role per user, whatever code assigns it (seeders, tinker, the app).
 */

beforeEach(function () {
    $this->editor = Role::findOrCreate(UserRoles::EDITOR->value, 'web');
    $this->agent = Role::findOrCreate(UserRoles::AGENT->value, 'web');
    $this->user = User::factory()->create();
});

test('a user can be given a role', function () {
    $this->user->assignRole($this->editor);

    expect($this->user->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
});

test('a second role is refused, and the first one stays', function () {
    $this->user->assignRole($this->editor);

    expect(fn () => $this->user->assignRole($this->agent))->toThrow(DomainException::class, 'only one');

    expect($this->user->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
});

test('two roles at once are refused', function () {
    expect(fn () => $this->user->assignRole($this->editor, $this->agent))->toThrow(DomainException::class);

    expect($this->user->fresh()->roles)->toBeEmpty();
});

test('assigning the role a user already has is fine', function () {
    $this->user->assignRole($this->editor);
    $this->user->assignRole(UserRoles::EDITOR->value);

    expect($this->user->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
});

test('syncRoles() changes a user\'s role', function () {
    $this->user->assignRole($this->editor);

    $this->user->syncRoles($this->agent);

    expect($this->user->fresh()->getRoleNames()->all())->toBe([UserRoles::AGENT->value]);
});

test('a role can be given before a new user is saved', function () {
    $user = User::factory()->make();
    $user->assignRole($this->editor);
    $user->save();

    expect($user->fresh()->getRoleNames()->all())->toBe([UserRoles::EDITOR->value]);
});
