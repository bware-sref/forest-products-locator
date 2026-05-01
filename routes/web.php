<?php

use App\Http\Controllers\FaqController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// use Laravel\Fortify\Features;
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

/**
 * Add a Business
 * MillController?
 */
Route::get('/add-business', [MillController::class, 'create'])
    ->name('add-business');
Route::post('/mill', [MillController::class, 'store'])
    ->name('store-mill');

/**
 * Show details of Mill specified by mill.match_id
 */
Route::get('/mill-list/{mill:match_id}', [MillController::class, 'show'])
    ->name('mill-list-item');

Route::get('/mills/export/', [MillController::class, 'export'])->name('mill-export');

/**
 * FAQ
 * PagesController
 */
Route::get('/faqs', [FaqController::class, 'index'])
    ->name('faqs');


/**
 * About Us
 * pages controller?
 */
Route::get('/about-us', function () {
    return Inertia::render('about-us', []);
})->name('about-us');

/**
 * State Resources
 * will probably get its own controller
 */
Route::get('/state-resources', function () {
    return Inertia::render('state-resources', []);
})->name('state-resources');

/**
 * Contact
 * PagesController
 */
Route::get('/contact', function () {
    return Inertia::render('contact', []);
})->name('contact');

/**
 * Site Map
 * use a generator of some sort?
 */
Route::get('/sitemap', function () {
    return Inertia::render('sitemap', []);
})->name('sitemap');

/**
 * Accessibility
 * PagesController
 */
Route::get('/accessibility', function () {
    return Inertia::render('accessibility', []);
})->name('accessibility');

/**
 * Again, Backpack so standard dashboard not needed.
 */
// Route::middleware(['auth', 'verified'])->group(function () {
//     Route::get('dashboard', function () {
//         return Inertia::render('dashboard');
//     })->name('dashboard');
// });

/**
 * We're using Backpack instead of standard user stuff so I don't think we need this.
 * I guess we'll see though.
 */
// require __DIR__.'/settings.php';
