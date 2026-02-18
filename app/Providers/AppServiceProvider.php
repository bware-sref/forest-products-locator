<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\ServiceProvider;

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
    }
}
