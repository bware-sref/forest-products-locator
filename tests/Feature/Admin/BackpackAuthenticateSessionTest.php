<?php

use App\Models\User;

/**
 * App\Http\Middleware\BackpackAuthenticateSession (our patched copy of Backpack's AuthenticateSession).
 *
 * In feature tests the auth guards (and the user they've resolved) live on the app instance across
 * requests, so forgetGuards() between requests makes each one re-resolve the user from the session the
 * way a real, separate request would.
 */
function freshRequest(): void
{
    app('auth')->forgetGuards();
}

function backpackLogin($test, User $user, string $password, bool $remember = false)
{
    return $test->post(route('backpack.auth.login'), array_filter([
        'email' => $user->email,
        'password' => $password,
        'remember' => $remember ? 'on' : null,
    ]));
}

it('keeps a user authenticated via actingAs() on the backpack guard', function () {
    // the middleware has to store the session password hash itself here; it used to store it with the
    // wrong HMAC key and then immediately fail its own check
    $this->actingAs(User::factory()->create(), backpack_guard_name());

    $this->get(route('backpack.dashboard'))->assertOk();
    $this->get(route('backpack.dashboard'))->assertOk();
});

it('keeps a user logged in through the login form authenticated across requests', function () {
    $user = User::factory()->create(['password' => 'correct-horse']);

    backpackLogin($this, $user, 'correct-horse')->assertRedirect(backpack_url('dashboard'));

    foreach (range(1, 3) as $ignored) {
        freshRequest();
        $this->get(route('backpack.dashboard'))->assertOk();
    }
});

it('logs the user out when their password changes after login', function () {
    $user = User::factory()->create(['password' => 'correct-horse']);
    backpackLogin($this, $user, 'correct-horse');

    // e.g. changed from another device, or by an admin
    $user->forceFill(['password' => 'battery-staple'])->save();

    freshRequest();
    $this->get(route('backpack.dashboard'))->assertRedirect(backpack_url('login'));
    expect(auth(backpack_guard_name())->check())->toBeFalse();
});

it('still accepts a raw password hash stored in the session by older code', function () {
    $user = User::factory()->create();

    $this->actingAs($user, backpack_guard_name())
        ->withSession(['password_hash_' . backpack_guard_name() => $user->getAuthPassword()])
        ->get(route('backpack.dashboard'))
        ->assertOk();
});

it('lets a remembered user back in on a new session', function () {
    $user = User::factory()->create(['password' => 'correct-horse']);
    $login = backpackLogin($this, $user, 'correct-horse', remember: true);

    $recallerName = auth(backpack_guard_name())->getRecallerName();
    $recaller = $login->getCookie($recallerName)?->getValue();
    expect($recaller)->not->toBeEmpty();

    // new browser session: no session data, just the remember-me cookie
    $this->flushSession();
    freshRequest();

    $this->withCookie($recallerName, $recaller)
        ->get(route('backpack.dashboard'))
        ->assertOk();
    expect(auth(backpack_guard_name())->viaRemember())->toBeTrue();
});
