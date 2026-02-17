<?php

namespace App\Http\Controllers;

use Exception;
use App\Exceptions\MillResourceRequestValidationException;
use App\Http\Requests\MillResourceRequest;
use App\Http\Resources\MillResource;
use App\Http\Resources\MillResourceCollection;
use App\Models\Mill;
use Illuminate\Http\Request;
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
        return new MillResourceCollection(Mill::apiSearch($request->validated()));
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
    public function show(Mill $mill)
    {
        // load MillType and WoodSpecies relationships
        $mill->load([
            'millTypes',
            'woodSpecies',
        ]);
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
