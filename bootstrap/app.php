<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\TrustProxies;

/**
 * New files/directories need to be group-writable regardless of which
 * entry point creates them (web via php-fpm, queue worker, artisan CLI),
 * since ec2-user (deploy) and apache (web/queue) both need access to
 * storage content no matter who created it. Set before anything writes to
 * disk, and here specifically since this file is loaded by both
 * public/index.php and artisan.
 * 
 * We want directories to end up with 775.
 * umask($mask) does logical AND on and $mask and 0777 to determine what 
 * permissions to remove.
 * E.g., (0002 & 0777) = 0002, yielding 775 permissions.
 * Also, remember that in PHP, a leading 0 on an integer indicates that the
 * integer is Octal!
 * PHP 8.1 added the more explicit syntax which uses an O (upper or lower)
 * after the leading 0.
 * E.g., 0O002 === 0o002 === 0002.
 */
umask(0002);

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * I'd love to be able to do an environment check before adding exceptions to encryptCookies()
         * but config() hasn't loaded at this point.
         * There's probably a way to do it.
         */
        $middleware->encryptCookies(except: [
            'appearance',
            'sidebar_state', // we don't need either of the above

            /**
             * Prevent invalidPayload DecryptExceptions caused by inspecting cookies from other apps on the same host.
             * XDebug and phpMyAdmin cookies are not encrypted and thus need to be ignored WRT encrypting cookies.
             */
            'XDEBUG_SESSION', // prevent invalidPayload DecryptExceptions
            'pma_lang', // phpMyAdmin language
            'phpMyAdmin', // session identification cookie
            'pmaAuth-1', // more pma
            'pmaUser-1', // more pma
            'pma_db_filename_template', // pma export file name template
        ]);

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
