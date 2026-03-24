<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMillRequest;
use App\Http\Requests\UpdateMillRequest;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class MillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // fetch all mills
        // make sure to eagerly load relationships!
        // let's omit the state and county from this query because we can easily map back to them with Mill data
        // $mills = Mill::with(['millTypes', 'woodSpecies', 'state', 'county'])->get();
        // $mills = Mill::with([
        //         'millTypes',
        //         'woodSpecies'
        //     ])->get();

        // we can collect values for filtering UI here
        // whichever is faster
        // we know we want to filter on state, mill type and mill species
        // also need to figure out distance filtering
        // probably something we can crib from the old version.

        return Inertia::render('mill-list-page', [
            'pageTitle' => 'Mill List',
            // 'mills' => $mills->toArray(),
            /**
             * we can forego the counties by just loading them onto the states
             * still need to only load the counties that have mills though.
             * 
             * Dagnabbit!
             * We can't use with() to fetch each states millTypes and woodSpecies...
             * Maybe I should just install the deep relationship package?
             */
            'states' => Inertia::once(fn() => State::has('mills')->with([
                'counties' => function ($query) {
                    $query->select('id', 'name', 'state_id')
                        ->has('mills')
                        ->orderBy('name', 'asc');
            }])->get(['id', 'name', 'abbreviation'])
                ->append(['value', 'label'])
                ->toArray()),
            // 'counties' => Inertia::once(fn() => County::has('mills')->get()->load('state')->toArray()),
            'millTypes' => Inertia::once(fn() => MillType::get(['id', 'name'])->toArray()),
            'woodSpecies' => Inertia::once(fn() => WoodSpecies::get(['id', 'name'])->toArray()),

            // easy way to inform the front end of the api url
            'millsApiUrl' => route('api.v1.mills'),
        ]);
    }

    /**
     * Display the mill map
     */
    public function map(Request $request)
    {
        return Inertia::render('mill-map', [
            'pageTitle' => 'Mill Map',
            // 'mills' => $mills->toArray(),
            /**
             * we can forego the counties by just loading them onto the states
             * still need to only load the counties that have mills though.
             * 
             * Dagnabbit!
             * We can't use with() to fetch each states millTypes and woodSpecies...
             * Maybe I should just install the deep relationship package?
             */
            'states' => Inertia::once(fn() => State::has('mills')->with([
                'counties' => function ($query) {
                    $query->select('id', 'name', 'state_id')
                        ->has('mills')
                        ->orderBy('name', 'asc');
            }])->get(['id', 'name', 'abbreviation'])
                ->append(['value', 'label'])
                ->toArray()),
            // 'counties' => Inertia::once(fn() => County::has('mills')->get()->load('state')->toArray()),
            'millTypes' => Inertia::once(fn() => MillType::get(['id', 'name'])->toArray()),
            'woodSpecies' => Inertia::once(fn() => WoodSpecies::get(['id', 'name'])->toArray()),

            // easy way to inform the front end of the api url
            'millsApiUrl' => route('api.v1.mills'),
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
