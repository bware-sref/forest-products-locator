<?php
/**
 * config/census-geocoder.php
 *
 * API spec: https://geocoding.geo.census.gov/geocoder/Geocoding_Services_API.html
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Root of the Census Bureau Geocoder REST API. Paths like
    | "locations/onelineaddress" and "geographies/coordinates" are appended
    | to this.
    |
    */

    'base_url' => env('CENSUS_GEOCODER_BASE_URL', 'https://geocoding.geo.census.gov/geocoder'),

    /*
    |--------------------------------------------------------------------------
    | HTTP Timeout
    |--------------------------------------------------------------------------
    |
    | Seconds to wait for a response before aborting.
    |
    */

    'timeout' => env('CENSUS_GEOCODER_TIMEOUT', 15),

    /*
    |--------------------------------------------------------------------------
    | Benchmark & Vintage
    |--------------------------------------------------------------------------
    |
    | The "current" benchmark's numerical id is 4. Geographies lookups also
    | need a vintage; "Current_Current" (also id 4) pairs with benchmark 4.
    | See https://geocoding.geo.census.gov/geocoder/benchmarks and
    | https://geocoding.geo.census.gov/geocoder/vintages?benchmark=4
    |
    */

    'benchmark' => env('CENSUS_GEOCODER_BENCHMARK', '4'),

    'vintage' => env('CENSUS_GEOCODER_VINTAGE', '4'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint Paths
    |--------------------------------------------------------------------------
    |
    | Appended to base_url. "benchmarks" lists all benchmarks the API
    | currently supports; "vintages" lists the vintages available for a
    | given benchmark. Both are useful for discovering replacement ids when
    | benchmark/vintage above go stale (Census retires old benchmarks
    | periodically).
    |
    */

    'endpoints' => [
        'benchmarks' => env('CENSUS_GEOCODER_BENCHMARKS_PATH', 'benchmarks'),
        'vintages' => env('CENSUS_GEOCODER_VINTAGES_PATH', 'vintages'),
    ],

];
