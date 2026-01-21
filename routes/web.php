<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

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
Route::get('/mill-map', function () {
    return Inertia::render('mill-map', []);
})->name('mill-map');
Route::get('/mill-list', function () {
    return Inertia::render('mill-list', []);
})->name('mill-list');
Route::get('/about-us', function () {
    return Inertia::render('about-us', []);
})->name('about-us');



Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

require __DIR__.'/settings.php';
