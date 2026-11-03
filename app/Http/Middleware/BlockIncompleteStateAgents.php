<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps State Agents without a valid state out of the admin panel (Superadmins and Administrators are exempt;
 * see User::isIncompleteStateAgent()).
 *
 * Every state restriction (SetsUpForStateAgents, FiltersByState, State Hub, the mill import) keys off
 * User::isStateAgent(), which is false without a state, so such a user would otherwise get the role's permissions
 * for every state. The admin form requires a state for the role, but roles can also be assigned elsewhere
 * (seeders, tinker), and a state can be deleted out from under a user. Fail closed until someone sets the state.
 *
 * Runs on every admin route after CheckIfAdmin (config/backpack/base.php). Logs the user out and explains why on
 * the login page, because Backpack's 403 page only shows a message with APP_DEBUG on.
 */
class BlockIncompleteStateAgents
{
    public const string MESSAGE = 'Your State Agent account has no state assigned yet. Ask a site administrator to set your state, then log in again.';

    public function handle(Request $request, Closure $next): Response
    {
        $user = backpack_user();

        if (! $user?->isIncompleteStateAgent()) {
            return $next($request);
        }

        Log::warning(self::class.": blocked admin access for State Agent user #{$user->id}, who has no valid state_id.");

        if ($request->expectsJson()) {
            abort(403, self::MESSAGE);
        }

        // Backpack's 403 page only shows a message when APP_DEBUG is on, so explain on the login page instead
        backpack_auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->guest(backpack_url('login'))
            ->withErrors([backpack_authentication_column() => self::MESSAGE]);
    }
}
