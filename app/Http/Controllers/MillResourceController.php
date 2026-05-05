<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\MillResourceRequestValidationException;
use App\Http\Requests\MillResourceRequest;
use App\Http\Resources\GeoJsonMillResource;
use App\Http\Resources\GeoJsonMillResourceCollection;
use App\Http\Resources\MillResource;
use App\Http\Resources\MillResourceCollection;
use App\Models\Mill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class MillResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(MillResourceRequest $request)
    {
        // filter Mills based on request parameters
        // do we want to make a model method for this?
        // yes.
        // if we store this request or the mills in the session, then we could just export the mills in the session...
        $validated = $request->validated();

        Log::debug('API params', ['validated' => $validated]);

        $mills = Mill::apiSearch($validated);

        // log requests that yield empty results.
        if (1 > count($mills)) {
            Log::debug('Empty Mill API request result: ', [
                'request.input' => collect($request->input())->toArray(),
                // 'mills' => $mills->toArray()
            ]);
        }
        if ($request->input('geojson')) {
            return new GeoJsonMillResourceCollection($mills);
        }
        return new MillResourceCollection($mills);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Mill $mill, Request $request)
    {
        // load MillType and WoodSpecies relationships
        $mill->load([
            'millTypes',
            'woodSpecies',
            'state',
            'county',
        ]);
        if ($request->input('geojson')) {
            return new GeoJsonMillResource($mill);
        }
        return new MillResource($mill);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Mill $mill)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Mill $mill)
    {
        //
    }
}
