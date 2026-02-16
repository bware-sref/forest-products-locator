<?php

use App\Http\Controllers\MillResourceController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
*/

// Route::resource('mills', MillResourceController::class)
//     ->only([
//         'index',
//         'show',
//     ]);

Route::get('/mills', 
    [
        MillResourceController::class,
        'index'
    ])->name('api-mills');

Route::get('/mill/{mill:match_id}', [MillResourceController::class, 'show'])
    ->name('api-mill');