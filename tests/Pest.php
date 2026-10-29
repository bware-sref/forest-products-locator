<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->beforeEach(function () {
        /**
         * Basset (Backpack's asset loader) registers its own "basset" disk at boot with
         * 'url' => url('').'/storage', i.e. an absolute APP_URL. Pest's browser tests serve the app from
         * their own server (127.0.0.1:<random port>), so admin pages pointed the test browser at the dev
         * server instead: cross-origin, so Tabler's CSS/JS (tagged integrity + crossorigin="anonymous")
         * were blocked by CORS, and every other admin asset only loaded if the dev server was up.
         * A relative disk URL lets Basset emit same-origin paths that Pest's server serves from public/.
         */
        config(['filesystems.disks.basset.url' => '/storage']);
    })
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Build a minimal GeoJSON FeatureCollection response body.
 * Shared by the ArcGIS service/exporter/config test suites.
 *
 * @param  array<int, array<string, mixed>>  $features
 */
function geojsonResponse(array $features): array
{
    return [
        'type'     => 'FeatureCollection',
        'features' => $features,
    ];
}

/**
 * A feature matching the "georgia-mills" schema.
 */
function georgiaFeature(int $objectId = 1, string $millName = 'Test Mill'): array
{
    return [
        'type'       => 'Feature',
        'geometry'   => ['type' => 'Point', 'coordinates' => [-83.5, 33.1]],
        'properties' => [
            'OBJECTID'        => $objectId,
            'MillName'        => $millName,
            'ProductNote'     => null,
            'Product1'        => 'Sawmill - Hardwood',
            'Product2'        => null,
            'Product3'        => null,
            'Symbology'       => 'Sawmill - Hardwood',
            'County'          => 'Clarke',
            'AddressPhysical' => '123 Timber Rd',
            'City'            => 'Athens',
            'State'           => 'GA',
            'Zip'             => '30601',
            'Telephone'       => '706-555-0100',
            'CEO'             => 'Jane Doe',
            'Mill_Type'       => 'Sawmill',
            'Mill_Size'       => 'B',
            'Species'         => 'HW',
            'Latitude'        => 33.1,
            'Longitude'       => -83.5,
        ],
    ];
}

/**
 * A feature matching the "primary_processors" schema.
 */
function northCarolinaFeature(int $objectId = 1, string $company = 'Acme Lumber'): array
{
    return [
        'type'       => 'Feature',
        'geometry'   => ['type' => 'Point', 'coordinates' => [-84.3, 33.7]],
        'properties' => [
            'OBJECTID' => $objectId,
            'Company'  => $company,
            'OP1'      => 'Sawmill, Pine',
            'Lat'      => 33.7,
            'Long'     => -84.3,
        ],
    ];
}

/**
 * Mill import pipeline helpers (tests/Feature/Jobs).
 * Mill's and State's factories are empty, so these build the bare minimum by hand.
 */
function makeImport(?int $stateId = null, array $attributes = []): \App\Models\Import
{
    return \App\Models\Import::create([
        'state_id'  => $stateId,
        'file_path' => 'imports/test.xlsx',
        'disk'      => 'local',
        'model'     => \App\Models\Mill::class,
        'status'    => \App\Enums\ImportStatus::Processing,
        ...$attributes,
    ]);
}

/**
 * A pending (i.e., freshly imported, unpublished) Mill.
 * Pending mills are hidden by Mill's global ApprovedScope; use freshMill() to reload one.
 */
function makePendingMill(array $attributes = []): \App\Models\Mill
{
    return \App\Models\Mill::create([
        'mill_name' => 'Test Mill '.\Illuminate\Support\Str::random(6),
        'status'    => \App\Enums\PublicationStatus::Pending,
        ...$attributes,
    ]);
}

/**
 * Reload a Mill regardless of its status.
 */
function freshMill(\App\Models\Mill $mill): ?\App\Models\Mill
{
    return \App\Models\Mill::withoutGlobalScopes()->find($mill->id);
}

/**
 * Admin panel helpers (tests/Feature/Admin).
 */
/**
 * Log in a user on the backpack guard with the given permissions (all 'web' guard, like the real ones).
 * Not actingAs(): it also makes backpack the default guard, so Spatie checks the 'web' permissions against the
 * wrong guard (see UploadFieldContrastTest).
 */
function loginAdminUser(array $permissions, ?\App\Enums\UserRoles $role = null, ?int $stateId = null): \App\Models\User
{
    $user = \App\Models\User::factory()->create(['state_id' => $stateId]);

    foreach ($permissions as $permission) {
        $user->givePermissionTo(\Spatie\Permission\Models\Permission::findOrCreate($permission, 'web'));
    }

    if ($role) {
        $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate($role->value, 'web'));
    }

    backpack_auth()->setUser($user);

    return $user;
}

function loginStateAgent(int $stateId, array $permissions): \App\Models\User
{
    return loginAdminUser($permissions, \App\Enums\UserRoles::AGENT, $stateId);
}

/**
 * The rows Backpack's list view would show (its DataTables search endpoint).
 */
function listRows($test, string $segment): array
{
    return $test->postJson("/admin/{$segment}/search", [], ['X-Requested-With' => 'XMLHttpRequest'])
        ->assertOk()
        ->json();
}
