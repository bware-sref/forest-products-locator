<?php

namespace App\Http\Middleware;

use Closure;

class CheckIfAdmin
{
    /**
     * Checked that the logged in user is an administrator.
     *
     * --------------
     * VERY IMPORTANT
     * --------------
     * If you have both regular users and admins inside the same table, change
     * the contents of this method to check that the logged in user
     * is an admin, and not a regular user.
     *
     * Additionally, in Laravel 7+, you should change app/Providers/RouteServiceProvider::HOME
     * which defines the route where a logged in user (but not admin) gets redirected
     * when trying to access an admin route. By default it's '/home' but Backpack
     * does not have a '/home' route, use something you've built for your users
     * (again - users, not admins).
     *
     * @param  ?\App\Models\User  $user
     * @return bool
     */
    private function checkIfUserIsAdmin($user)
    {
        // return ($user->is_admin == 1);
        // return true;
        /**
         * Every user is an admin-panel user, but only with at least one role or direct permission:
         * a user with neither is suspended (User::hasAdminAccess()).
         */
        return null !== $user && $user->hasAdminAccess();
    }

    public const string NO_ACCESS_MESSAGE = 'Your account doesn\'t have access to the admin panel. Ask a site administrator if you think it should.';

    /**
     * A logged-in user without access. Sending them to the login page alone would loop (Backpack redirects
     * logged-in users from there to the dashboard), so log them out and explain there.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUserWithoutAccess($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(self::NO_ACCESS_MESSAGE, 403);
        }

        backpack_auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(backpack_url('login'))
            ->withErrors([backpack_authentication_column() => self::NO_ACCESS_MESSAGE]);
    }

    /**
     * Answer to unauthorized access request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    private function respondToUnauthorizedRequest($request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            return response(trans('backpack::base.unauthorized'), 401);
        } else {
            return redirect()->guest(backpack_url('login'));
        }
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
        if (backpack_auth()->guest()) {
            return $this->respondToUnauthorizedRequest($request);
        }

        if (! $this->checkIfUserIsAdmin(backpack_user())) {
            return $this->respondToUserWithoutAccess($request);
        }

        return $next($request);
    }
}
