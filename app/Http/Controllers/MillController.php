<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMillRequest;
use App\Http\Requests\UpdateMillRequest;
use App\Models\Mill;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // fetch all mills
        $mills = Mill::all();

        // we can collect values for filtering UI here
        // whichever is faster
        // we know we want to filter on state, mill type and mill species
        // also need to figure out distance filtering
        // probably something we can crib from the old version.
        // $typeValues = 'distinct mill type values';

        return Inertia::render('mill-list', [
            'mills' => $mills,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMillRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Mill $mill)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateMillRequest $request, Mill $mill)
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
