<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $this->get(route('backpack.dashboard'))->assertRedirect(route('backpack.auth.login'));
});

test('authenticated users can visit the dashboard', function () {
    /**
     * actingAs() is spozta be the fake login.
     * However, since we might be using Backpack's auth guards, we need to specify that when invoking actingAs().
     * Also, it might still break if we don't assign some roles and/or permissions to the new User.
     */
    $user = User::factory()->create();

    $backpackGuard = config('backpack.base.guard', 'backpack');

    $this->actingAs($user, $backpackGuard);
    /**
     * The following fails because creating the user does not automatically authenticate them.
     */
    $this->get(route('backpack.dashboard'))->assertOk();
});