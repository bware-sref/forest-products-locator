<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

	// specify trusted proxies!
	// we have to use the booted() method if we want to store proxy IPs in a env config because
	// the configs haven't been loaded yet when this executes.
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->booted(function (Application $app): void {
	// specify IPs of trusted proxies, if there are any
	if (! empty(config('app.trust_proxies'))) {
	    TrustProxies::at(config('app.trust_proxies'));
	}
    })->create();
