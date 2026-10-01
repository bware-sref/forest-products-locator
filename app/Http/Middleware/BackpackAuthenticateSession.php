<?php

namespace app\Http\Middleware;

use Closure;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Session\Middleware\AuthenticateSession as LaravelAuthenticateSession;

class BackpackAuthenticateSession extends LaravelAuthenticateSession
{
    /**
     * The authentication factory implementation.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    protected $user;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth)
    {
        $this->auth = $auth;
        $this->user = backpack_user();
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (! $request->hasSession() || ! $this->user) {
            return $next($request);
        }

        if ($this->guard()->viaRemember()) {
            $passwordHash = explode('|', $request->cookies->get($this->guard()->getRecallerName()))[2] ?? null;

            if (! $passwordHash || $passwordHash != $this->user->getAuthPassword()) {
                $this->logout($request);
            }
        }

        if (! $request->session()->has('password_hash_'.backpack_guard_name())) {
            $this->storePasswordHashInSession($request);
        }

        /**
         * This is what breaks login.
         * Backpack compares the Bcrypt password from the DB, getAuthPassword(), to
         * a rehashed version of the password put in the session by Sanctum.
         * As you might guess, they dont' match.
         * Instead, we should be able to just use the parent class method
         * validatePasswordHash()
         * args are guard, password from DB, password from session
         */
        // if ($request->session()->get('password_hash_'.backpack_guard_name()) !== $this->user->getAuthPassword()) {
        if (! $this->validatePasswordHash(
            $this->user->getAuthPassword(),
            $request->session()->get('password_hash_'.backpack_guard_name())
            )) {
            $this->logout($request);
        }

        return tap($next($request), function () use ($request) {
            // if (! is_null($this->guard()->user())) {
            if (null !== $this->guard()->user()) {
                $this->storePasswordHashInSession($request);
            }
        });
    }

    /**
     * Store the user's current password hash in the session.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    protected function storePasswordHashInSession($request)
    {
        if (! $this->user) {
            return;
        }

        $request->session()->put([
            'password_hash_'.backpack_guard_name() => self::hashPasswordForCookie(
                $this->user->getAuthPassword()
            ),
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
     * Get the guard instance that should be used by the middleware.
     *
     * @return \Illuminate\Contracts\Auth\Factory|\Illuminate\Contracts\Auth\Guard
     */
    protected function guard()
    {
        return $this->auth;
    }

    /**
     * Create a HMAC of the password hash for storage in cookies.
     * Lifted from Illuminate\Auth\SessionGuard
     *
     * @param  string  $passwordHash
     * @return string
     */
    public function hashPasswordForCookie($passwordHash)
    {
        return hash_hmac(
            'sha256',
            $passwordHash,
            $this->hashKey ?? 'base-key-for-password-hash-mac'
        );
    }
}
