<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\GeocodingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
// use Laravel\Fortify\Features;
use App\Http\Controllers\MillController;
use App\Http\Controllers\StateResourceController;

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

Route::match(['get', 'post'], '/mills/export/', [MillController::class, 'export'])
    ->name('mill-export');

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
// Route::get('/state-resources', function () {
//     return Inertia::render('state-resources', []);
// })->name('state-resources');

Route::get('/state-resources', [StateResourceController::class, 'index'])
    ->name('state-resources');

/**
 * Do we want to use state abbreviation or name?
 * Let's go with abbreviation for now because we don't have to make a slug out of it
 * Slugs are no big deal, yo!
 * The state abbreviations look weirder, IMO.
 * Oh right.
 * State doesn't have a slug column.
 */
Route::get('/state-resources/{state:slug}', [StateResourceController::class, 'byState'])
    ->name('state-resources.by-state');

/**
 * How do we want to show individual state resources?
 * /state-resources/{state.abbreviation}/{id or slug?}
 */
Route::get('/state-resources/{state:slug}/{stateResource}', [StateResourceController::class, 'show'])
    ->name('state-resources.show');

/**
 * Contact
 * PagesController?
 * Old URL slug is sec_contact-info
 * We should probably redirect that one to a less bonkers URL.
 * And we probably need a controller for handling these.
 * And we may as well store submissions in the DB.
 */
Route::get('/contact', [ContactController::class, 'index'])->name('contact');

Route::post('/contact', [ContactController::class, 'store'])
    ->name('store-contact');

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
 * Geocoding Routes
 */
Route::controller(GeocodingController::class)
    ->prefix('geocoding')
    ->name('geocoding.')
    ->group(function () {
    /**
     * Allowing get and post makes testing easier
     */
    Route::match(['get', 'post'], '/geocode', 'geocode')
        ->name('geocode');

    Route::match(['get', 'post'], '/reverse', 'reverseGeocode')
        ->name('reverse');
});

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

/**
 * Prevent noise exceptions caused by Chrome dev tools running against localhost
 */
if (app()->environment('local')) {
    Route::get('/.well-known/appspecific/com.chrome.devtools.json', function () {
        return response()->json([
            'workspace' => [
                'root' => base_path(),
                'uuid' => '7bc8a113-bc75-472e-89a1-b663b0a29ef1' // Any random UUID string
            ]
        ]);
    });
}
