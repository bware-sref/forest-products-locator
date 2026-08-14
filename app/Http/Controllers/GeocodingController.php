<?php

namespace App\Http\Controllers;

use App\Enums\AwsLocationIntendedUse;
use App\Services\GeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class GeocodingController extends Controller
{
    //
    public function __construct(protected GeocodingService $geocoding)
    {}

    public function geocode(Request $request): JsonResponse
    {
        /**
         * We might add user's location for BiasPosition
         * params that are only required if both are present
         */
        $request->validate([
            'address' => 'required|string|max:500'
        ]);

        $results = $this->geocoding->geocode(
            queryText: $request->input('address'),
            intendedUse: AwsLocationIntendedUse::SingleUse,
        );

        return response()->json(['results' => $results]);
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        // dd($request);

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        /**
         * I changed the type of longitude and latitude from float to int|float|string so we can let reverse() 
         * handle casting them to floats so we don't have to do it everywhere
         */
        $result = $this->geocoding->reverse(
            longitude: $request->input('longitude'),
            latitude: $request->input('latitude'),
            intendedUse: AwsLocationIntendedUse::SingleUse,
        );

        return response()->json(['result' => $result]);
    }
}
