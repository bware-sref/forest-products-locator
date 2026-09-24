<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Inertia\ExceptionResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // prevent wrapping non-paginated JsonResources inside a top-level data key
        // NOTE: paginated JsonResources always wrap because they include pagination metadata
        // so we don't want to paginate ours Mill JsonResources
        JsonResource::withoutWrapping();

        /**
         * Gemini has suggested that modern Laravel discourages creating aliases like I've done below.
         * Instead, just import the damn class into the files where it's needed and use 'as' to alias in place.
         * I'm leaving it for now, but making this note.
         */
        $loader = AliasLoader::getInstance();
        $loader->alias('AWS', \Aws\Laravel\AwsFacade::class);

        /**
         * Bind our UserCrudController to Backpack Permission Manager's UserCrudController
         */
        $this->app->bind(
            \Backpack\PermissionManager\app\Http\Controllers\UserCrudController::class,
            \App\Http\Controllers\Admin\UserCrudController::class
        );

        /**
         * GeocodingController is a public, unauthenticated pass-through to AWS
         * GeoPlaces, which bills per request, so it needs its own rate limiter
         * rather than relying on the (unconfigured, effectively no-op) default
         * 'api' limiter. Keyed by IP since there's no auth on these routes.
         * Values live in config/geocoding.php (env-overridable) rather than
         * here, so they can be tuned per-environment without a code change.
         *
         * Two windows so a burst-only limit can't be quietly worked around by
         * spreading requests out: perMinute allows legitimate address-typo
         * retries; perDay caps sustained abuse that stays under the per-minute
         * ceiling. Only `geocode` sees real traffic today (`reverseGeocode`
         * isn't wired up in the frontend yet) — revisit these numbers once it
         * is, since usage patterns (and therefore reasonable limits) may differ.
         */
        RateLimiter::for('geocoding', function (Request $request) {
            return [
                Limit::perMinute(config('geocoding.rate_limits.per_minute'))
                    ->by('minute:'.$request->ip()),
                Limit::perDay(config('geocoding.rate_limits.per_day'))
                    ->by('day:'.$request->ip()),
            ];
        });

        /**
         * API Requests
         */
        RateLimiter::for('api', fn (Request $request) => [
            Limit::perMinute(config('rate-limits.api.per_minute'))
                ->by('minute:'.$request->ip()),
            Limit::perDay(config('rate-limits.api.per_day'))
                ->by('day:'.$request->ip()),
        ]);

        /**
         * Contact
         */
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perMinute(config('rate-limits.contact.per_minute'))
                ->by('minute:'.$request->ip()),
            Limit::perDay(config('rate-limits.contact.per_day'))
                ->by('day:'.$request->ip()),
        ]);

        /**
         * Mill Export
         */
        RateLimiter::for('export', fn (Request $request) => [
            Limit::perMinute(config('rate-limits.export.per_minute'))
                ->by('minute:'.$request->ip()),
            Limit::perDay(config('rate-limits.export.per_day'))
                ->by('day:'.$request->ip()),
        ]);

        /**
         * Mill Create/Edit
         */
        RateLimiter::for('mills', fn (Request $request) => [
            Limit::perMinute(config('rate-limits.mills.per_minute'))
                ->by('minute:'.$request->ip()),
            Limit::perDay(config('rate-limits.mills.per_day'))
                ->by('day:'.$request->ip()),
        ]);

        /**
         * Add an Inertia exception handler
         */
        Inertia::handleExceptionsUsing(function (ExceptionResponse $response) {
            if (\in_array($response->statusCode(), config('inertia.error_codes', []))) {
                return $response->render('error', [
                    'status' => $response->statusCode(),
                ])->withSharedData();
            }
        });
    }
}
