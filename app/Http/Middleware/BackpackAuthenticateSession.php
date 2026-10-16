<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Session\Middleware\AuthenticateSession as LaravelAuthenticateSession;

/**
 * Patched copy of Backpack\CRUD\app\Http\Middleware\AuthenticateSession.
 *
 * Backpack's version compares the raw password hash from the DB, getAuthPassword(), to the session's
 * password_hash_<guard>. Laravel's SessionGuard::login() now stores an HMAC of that hash instead, so
 * Backpack's comparison never matched and every admin login was immediately logged out (2026-09-30).
 * @TODO: check whether Backpack has caught up, and switch back to its middleware if so.
 *
 * Differences from Backpack's version:
 * - Hashes are stored and checked with the Backpack guard's own hashPasswordForCookie() (HMAC keyed with
 *   app.key) via the parent's validatePasswordHash(), which also still accepts a raw hash in the session.
 *   An earlier patch stored hashes with a locally copied hashPasswordForCookie() whose key was never set, so
 *   whenever this middleware had to store the hash itself (e.g. actingAs() in tests) the check that
 *   followed always failed and logged the user out.
 * - guard() returns the Backpack guard. Backpack's returned the auth manager, which proxies to the
 *   *default* (web) guard, so its remember-me check, post-request hash refresh, and logout all looked at
 *   the wrong guard.
 * - The user is looked up per request rather than captured when the middleware is constructed.
 */
class BackpackAuthenticateSession extends LaravelAuthenticateSession
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = backpack_user();

        if (! $request->hasSession() || ! $user) {
            return $next($request);
        }

        if ($this->guard()->viaRemember()) {
            // the recaller cookie is "id|token|password hash HMAC"
            $passwordHash = explode('|', (string) $request->cookies->get($this->guard()->getRecallerName()))[2] ?? null;

            if (! $passwordHash || ! $this->validatePasswordHash($user->getAuthPassword(), $passwordHash)) {
                $this->logout($request);
            }
        }

        if (! $request->session()->has($this->sessionKey())) {
            $this->storePasswordHashInSession($request);
        }

        if (! $this->validatePasswordHash($user->getAuthPassword(), $request->session()->get($this->sessionKey()))) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            if (null !== $this->guard()->user()) {
                $this->storePasswordHashInSession($request);
            }
        });
    }

    /**
     * Store the user's current password hash in the session, in the same HMAC form SessionGuard::login() uses.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function storePasswordHashInSession($request)
    {
        $user = $this->guard()->user();

        if (! $user) {
            return;
        }

        $request->session()->put([
            $this->sessionKey() => $this->guard()->hashPasswordForCookie($user->getAuthPassword()),
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    protected function logout($request)
    {
        $this->guard()->logoutCurrentDevice();

        $request->session()->flush();

        \Alert::error(trans('backpack::base.session_expired_error'))->flash();

        throw new AuthenticationException('Unauthenticated.', [backpack_guard_name()], backpack_url('login'));
    }

    /**
     * The Backpack guard (a SessionGuard), not the auth manager.
     *
     * @return \Illuminate\Auth\SessionGuard
     */
    protected function guard()
    {
        return $this->auth->guard(backpack_guard_name());
    }

    protected function sessionKey(): string
    {
        return 'password_hash_' . backpack_guard_name();
    }
}
