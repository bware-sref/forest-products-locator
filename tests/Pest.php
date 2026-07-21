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
