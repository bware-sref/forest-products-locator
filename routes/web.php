<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GeocodingController;
use App\Http\Controllers\MillController;
use App\Http\Controllers\StatePageController;
use App\Http\Controllers\StateResourceController;
use App\Models\PageSeo;
// use Laravel\Fortify\Features;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome', [
        // 'canRegister' => Features::enabled(Features::registration()),
        // Not config('app.name') here -- Inertia's title callback already
        // appends " - {app name}" globally, so that would duplicate it.
        'pageSeo' => PageSeo::resolve(
            'home',
            'Home',
            'Find sawmills, pulp mills, and other forest product processors near you.'
        ),
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
    ->name('mills.map');

// Route mill-list to MillController::index()
Route::get('/mill-list', [MillController::class, 'index'])
    ->name('mills.index');

/**
 * Add a Business
 * MillController?
 */
Route::get('/add-business', [MillController::class, 'create'])
    ->name('mills.create');
Route::post('/mills', [MillController::class, 'store'])
    ->name('mills.store');
/**
 * Edit a Mill
 */
Route::get('/mills/{mill:match_id}/edit', [MillController::class, 'edit'])
    ->name('mills.edit');
/**
 * Patch seems more appropriate for this because it's spozta be a partial update.
 */
Route::match(['patch', 'put'], '/mills/{mill:match_id}', [MillController::class, 'update'])
    ->name('mills.update');

/**
 * Show details of Mill specified by mill.match_id
 * This URL pattern was chosen because it mirrors the URL pattern on the old version.
 */
Route::get('/mill-list/{mill:match_id}', [MillController::class, 'show'])
    ->name('mills.show');

Route::match(['get', 'post'], '/mills/export/', [MillController::class, 'export'])
    ->name('mills.export');

/**
 * FAQ
 * PagesController
 */
Route::get('/faqs', [FaqController::class, 'index'])
    ->name('faqs.index');

/**
 * About Us
 * pages controller?
 */
Route::get('/about-us', function () {
    return Inertia::render('about-us', [
        'pageSeo' => PageSeo::resolve(
            'about-us',
            'About Us',
            'Learn about the mission behind the Forest Products Locator.'
        ),
    ]);
})->name('about-us');

/**
 * State Pages
 * The per-state marketing page (hero, contacts, forest overview, economic
 * impact, forestry agency/assistance) -- distinct from /state-resources,
 * which lists StateResource records.
 */
Route::get('/states', [StatePageController::class, 'index'])
    ->name('states.index');

Route::get('/states/{state:slug}', [StatePageController::class, 'show'])
    ->name('states.show');

/**
 * Contact
 * PagesController?
 * Old URL slug is sec_contact-info
 * We should probably redirect that one to a less bonkers URL.
 * And we probably need a controller for handling these.
 * And we may as well store submissions in the DB.
 */
Route::get('/contact', [ContactController::class, 'index'])
    ->name('contacts.create');

Route::post('/contact', [ContactController::class, 'store'])
    ->name('contacts.store');

/**
 * Redirect the route to map the contact page URL from the old site to the new one
 */
Route::permanentRedirect('/sec_contact-info', '/contact');

/**
 * Geocoding Routes
 * Have been moved to api.php
 */

/**
 * Prevent noise exceptions caused by Chrome dev tools running against localhost
 */
if (app()->environment('local')) {
    Route::get('/.well-known/appspecific/com.chrome.devtools.json', function () {
        return response()->json([
            'workspace' => [
                'root' => base_path(),
                'uuid' => '7bc8a113-bc75-472e-89a1-b663b0a29ef1', // Any random UUID string
            ],
        ]);
    });
}
