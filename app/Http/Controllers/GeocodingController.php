<?php

namespace App\Http\Controllers;

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
        $request->validate(['address' => 'required|string|max:500']);

        $results = $this->geocoding->geocode($request->input('address'));

        return response()->json(['results' => $results]);
    }

    public function reverseGeocode(Request $request): JsonResponse
    {
        // dd($request);

        $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $result = $this->geocoding->reverse(
            (float) $request->input('longitude'),
            (float) $request->input('latitude')
        );

        return response()->json(['result' => $result]);
    }
}
