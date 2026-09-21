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
        Log::debug("\n".self::class."::respond():\n dumping request:\n", [
            "request\n" => $request,
            "\nroute:\n" => $request->route(),
        ]);
        
        /** 
         * We might not be able to use Inertia for this.
         */
        Inertia::flash([
            'type' => 'success',
            'message' => 'Thank you for your submission.',
        ]);
        return redirect()->back();
        // return to_route($request->route());
    }
}
