<?php

use App\Http\Controllers\MillResourceController;
use App\Http\Controllers\GeocodingController;
use Illuminate\Support\Facades\Route;

/**
 * Prefix this group of API routes with v1.
 * The result should be like /api/v1/mills
 * Also add a prefix to all the route names in this group.
 */
Route::name('api.v1.')
    ->prefix('v1')
    ->group(function() {

    /**
     * Group the mills endpoints together.
     */
    Route::controller(MillResourceController::class)
        ->prefix('mills')
        ->name('mills.')
        ->group(function () {

            /**
             * mills.index
             * accept get and post requests
             */
            Route::match(['get', 'post'], '/', 'index')
                ->name('index');

            /**
             * mills.show
             */
            Route::get('/{mill:match_id}', 'show')
                ->name('show');                
        });

    /**
     * Geocoding Routes
     */
    Route::controller(GeocodingController::class)
        ->prefix('geocoding')
        ->name('geocoding.')
        ->middleware('throttle:geocoding')
        ->group(function () {
            /**
             * Allowing get and post makes testing easier
             */
            Route::match(['get', 'post'], '/geocode', 'geocode')
                ->name('geocode');

            Route::match(['get', 'post'], '/reverse', 'reverseGeocode')
                ->name('reverse');
        });

});
