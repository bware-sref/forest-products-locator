<?php
/**
 * config/geocoding.php
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Applied to the /api/v1/geocoding/* routes via the 'geocoding' rate
    | limiter (see App\Providers\AppServiceProvider::boot()). Those routes
    | are public/unauthenticated and pass through to AWS GeoPlaces, which
    | bills per request, so both windows are keyed by IP.
    |
    | per_minute — allows a real user a few address-typo retries.
    | per_day    — backstop against sustained abuse that stays under the
    |              per-minute ceiling.
    |
    */

    'rate_limits' => [
        'per_minute' => env('GEOCODING_RATE_LIMIT_PER_MINUTE', 10),
        'per_day' => env('GEOCODING_RATE_LIMIT_PER_DAY', 200),
    ],

];
