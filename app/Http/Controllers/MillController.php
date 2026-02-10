<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMillRequest;
use App\Http\Requests\UpdateMillRequest;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;
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
        // make sure to eagerly load relationships!
        $mills = Mill::with(['millTypes', 'woodSpecies', 'state', 'county'])->get();
        

        // we can collect values for filtering UI here
        // whichever is faster
        // we know we want to filter on state, mill type and mill species
        // also need to figure out distance filtering
        // probably something we can crib from the old version.

        return Inertia::render('mill-list', [
            'mills' => $mills->toArray(),
            'states' => Inertia::once(fn() => State::has('mills')->get()->toArray()),
            'counties' => Inertia::once(fn() => County::has('mills')->get()->load('state')->toArray()),
            'millTypes' => Inertia::once(fn() => MillType::all()->toArray()),
            'woodSpecies' => Inertia::once(fn() => WoodSpecies::all()->toArray()),
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
     * 
     * NOTE: to avoid the need to create redirects for all individual mill pages,
     * MillController::show() needs to be routed to /mill-list/{match_id} (no trailing slash).
     * See database/data/searchMills.json for examples of old URLs.
     */
    public function show(Mill $mill)
    {
        // load relationships
        $mill->load([
            'millTypes',
            'woodSpecies',
            'state',
            'county',
        ]);
        return Inertia::render('mill-list-item', [
            'mills' => [$mill],
        ]);
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
