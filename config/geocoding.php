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

    /**
     * Bias Position
     * 
     * Bias Position is the position that location search results should be close to.
     * This should reduce the sort of weirdness I encountered when searching "120 lakeshore" and the results were in Canada.
     * 
     * OMG, I gotta go doody.
     * 
     * Anyway, when included in an Amazon Location Service API request, it's formatted as an array ordered as [X,Y],
     * meaning [longitude, latitude] rather than how they're typical ordered in layperson speech.
     * X, Y is apparently old hat for the geospatial folks, not just because they're spatial, but because they're geospatial. :-D
     * Our default coordinates were determined by finding the rough bounding box for the southeastern states and then finding the
     * center.
     * Deets below
     * Kill Devil Hills, NC (outer banks) is just shy of 75° W (-75.71) so -75.
     * El Paso, TX is just over 106° W (-106.58) so -107.
     * The north western corner of Oklahoma is about 36.99° N, but the northern tip of Virginia is at 39.36° N, so 40.
     * The southern tip of Texas is about 25.86° N but Key West is only about 24.57° N, so 24.
     * Remember, it doesn't need to insanely precise, just enough to minimize results that are way off.
     * 
     * ((-107 - -75) / 2) + -75 = -91.0
     * ((40 - 24) / 2) + 24 = 32.0
     * I can't remember if adding .0 actually makes PHP treat it as a float or not.
     * 
     * Also, should this be our default map center?
     * 
     * I waffled between using long names versus x & y.
     * Look what won!
     */
    'bias_position' => [
        'x' => env('GEOCODING_BIAS_POSITION_X', -91.0),
        'y' => env('GEOCODING_BIAS_POSITION_Y', 32.0),
    ],

    /**
     * Filter
     *
     * We can restrict results to specific countries as well as by place type using IncludeCountries and IncludePlaceTypes filters.
     * https://docs.aws.amazon.com/location/latest/APIReference/API_geoplaces_GeocodeFilter.html#API_geoplaces_GeocodeFilter_Contents
     *
     * Specify countries with ISO-3166-1 alpha-2 (two-letter) or alpha-3 (guess) codes.
     * https://en.wikipedia.org/wiki/List_of_ISO_3166_country_codes#Current_ISO_3166_country_codes
     * 
     * Valid PlaceType values are in Amazon docs.
     * This application is probably only interested in PointAddress, PointOfInterest, and InterpolatedAddress, in that order.
     */
    'filter' => [
        'countries' => explode(',', env('GEOCODING_FILTER_COUNTRIES', 'USA')),
        'place_types' => explode(',', env('GEOCODING_FILTER_PLACE_TYPES', 'PointAddress,PointOfInterest,InterpolatedAddress')),
    ],
];
