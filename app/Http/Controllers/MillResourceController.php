<?php

namespace App\Http\Controllers;

use App\Http\Resources\MillResource;
use App\Http\Resources\MillResourceCollection;
use App\Models\Mill;
use Illuminate\Http\Request;

class MillResourceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // filter Mills based on request parameters
        $mills = Mill::all();
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
