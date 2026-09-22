<?php

namespace App\Http\Responders;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Spatie\Honeypot\SpamResponder\SpamResponder;
use Symfony\Component\HttpFoundation\Response;

class InertiaSpamResponder implements SpamResponder
{

    protected const string SESSION_KEY = 'spamRedirect';

    public function respond(Request $request, Closure $next): Response
    {
        Log::debug("\n".self::class."::respond():\nSpam Form Request!\n", [
            // "\nrequest\n" => $request,
            // "\nroute:\n" => $request->route(),
            "\nfullURL:" => $request->fullUrl(),
            "\nips:" => $request->ips(),
            "\nhasXInertia:" => $request->hasHeader('X-Inertia'),
        ]);
        /**
         * Do we maybe want to log the IPs of spammers?
         */

        $flash = [
            'type' => 'success',
            'message' => 'Thank you for your submission.',
        ];

        /**
         * NOTE: the header value is a string rather than a boolean
         */
        if ($request->hasHeader('X-Inertia')) {
            /**
             * The flash didn't display when we returned here with back() chained on flash().
             * :shrugs:
             */
            Inertia::flash($flash);
        } else {
            session()->flash($flash['type'], $flash['message']);
        }

        /**
         * If a redirect location was specified in teh session, use it!
         */
        if (self::hasSessionRedirect()) {
            Log::debug("\n".self::class."::respond():\nfound session ".self::SESSION_KEY."!", [
                self::SESSION_KEY => self::getSessionRedirect(),
            ]);
            return self::doSessionRedirect();
        }

        /**
         * Because we're responding to a POST request, we have to use redirect()->back()
         * instead of just using back().
         * That's why returning flash()->back() resulted in no Flash!
         * However, we don't always want to go back to the form.
         * For example, when the Edit Mill is successfully submitted, the browser returns to the Mill's single page.
         */
        return redirect()->back();
    }

    public function hasSessionRedirect(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function getSessionRedirect()
    {
        return session()->get(self::SESSION_KEY);
    }

    protected function doSessionRedirect(): Response
    {
        $route = session()->pull(self::SESSION_KEY);
        // session()->forget(self::SESSION_KEY);
        return redirect()->to($route);
    }

    /**
     * We could use a secondary key to handle multiple redirect values for the same user...
     * As long as the redirects correspond to different URLs...
     * maybe in the future
     * For now, we just need a single redirect for each user session.
     * The main thing these methods offer is an easy way to avoid using our session key elsewhere.
     *
     * @param string $url URL to which to redirect
     * @return bool
     */
    public static function setRedirect(string $url): void
    {
        session()->put(self::SESSION_KEY, $url);
    }

    public static function clearRedirect(?string $url = ''): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
