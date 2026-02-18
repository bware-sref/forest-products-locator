<?php

use App\Http\Controllers\MillResourceController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

/**
 * Prefix this group of API routes with v1.
 * The result should be like /api/v1/mills
 * Also add a prefix to all the route names in this group.
 */
Route::name('api.v1.')->prefix('v1')->group(function() {

    /**
     * accept get and post requests
     */
    Route::match(['get', 'post'], '/mills', [MillResourceController::class, 'index'])
        ->name('mills');

    /**
     * Is there any reason to accept posts on this endpoint?
     */
    Route::get('/mill/{mill:match_id}', [MillResourceController::class, 'show'])
        ->name('mill');
});
