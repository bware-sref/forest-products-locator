<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\MillController;

Route::get('/', function () {
    return Inertia::render('welcome', [
        // 'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

/**
 * Add routes for all pages.
 * mill-map
 * mill-list
 * about-us
 */

// Consider changing the name of the controller action.
Route::get('/mill-map', [MillController::class, 'map'])
    ->name('mill-map');

// Route mill-list to MillController::index()
Route::get('/mill-list', [MillController::class, 'index'])
    ->name('mill-list');
Route::get('/mill-list/{mill:match_id}', [MillController::class, 'show'])
    ->name('mill-list-item');

Route::get('/about-us', function () {
    return Inertia::render('about-us', []);
})->name('about-us');
Route::get('/state-resources', function () {
    return Inertia::render('state-resources', []);
})->name('state-resources');
Route::get('/add-business', function () {
    return Inertia::render('add-business', []);
})->name('add-business');
Route::get('/faq', function () {
    return Inertia::render('faq', []);
})->name('faq');
Route::get('/contact', function () {
    return Inertia::render('contact', []);
})->name('contact');
Route::get('/sitemap', function () {
    return Inertia::render('sitemap', []);
})->name('sitemap');
Route::get('/accessibility', function () {
    return Inertia::render('accessibility', []);
})->name('accessibility');



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
