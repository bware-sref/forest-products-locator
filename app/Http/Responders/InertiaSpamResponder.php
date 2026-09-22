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
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function respond(Request $request, Closure $next)
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
         * Because we're responding to a POST request, we have to use redirect()->back()
         * instead of just using back().
         * That's why returning flash()->back() resulted in no Flash!
         * However, we don't always want to go back to the form.
         * For example, when the Edit Mill is successfully submitted, the browser returns to the Mill's single page.
         */
        return redirect()->back();
    }
}
