<?php

namespace App\Providers;

use Illuminate\Foundation\AliasLoader;
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


    }
}
