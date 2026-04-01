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
        return Inertia::render('mill-list-page', [
            'pageTitle' => 'Mill List',
            ...$this->getData($request),
            /**
             * we can forego the counties by just loading them onto the states
             * still need to only load the counties that have mills though.
             * 
             * Dagnabbit!
             * We can't use with() to fetch each states millTypes and woodSpecies...
             * Maybe I should just install the deep relationship package?
             */
            // 'states' => Inertia::once(fn() => State::has('mills')->with([
            //     'counties' => function ($query) {
            //         $query->select('id', 'name', 'state_id')
            //             ->has('mills')
            //             ->orderBy('name', 'asc');
            // }])->get(['id', 'name', 'abbreviation'])
            //     ->append(['value', 'label'])
            //     ->toArray()),
            // // 'counties' => Inertia::once(fn() => County::has('mills')->get()->load('state')->toArray()),
            // 'millTypes' => Inertia::once(fn() => MillType::get(['id', 'name'])->toArray()),
            // 'woodSpecies' => Inertia::once(fn() => WoodSpecies::get(['id', 'name'])->toArray()),

            // // easy way to inform the front end of the api url
            // 'millsApiUrl' => route('api.v1.mills'),
        ]);
    }

    /**
     * Display the mill map
     */
    public function map(Request $request)
    {
        return Inertia::render('mill-map-page', [
            'pageTitle' => 'Mill Map',
            ...$this->getData($request),
        ]);
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
            'pageTitle' => $mill->mill_name . ' | Details',
            'mills' => [$mill],
        ]);
    }

    /**
     * Display the Mill form
     */
    public function create()
    {
        return Inertia::render('add-business', [
            'pageTitle' => 'Add Your Business',
            'states' => Inertia::once(fn() => 
                State::getWithCounties(
                    cols: ['id', 'name', 'abbreviation'],
                    countyCols: ['id', 'name', 'state_id']            
                )->toArray()
            ),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Accepts POST from create (mill form)
     */
    public function store(StoreMillRequest $request)
    {
        if ($request->validated()) {

            Inertia::flash([
                'message' => 'Successfully submitted Mill!',
                'newMillId' =>  rand(1187, 2000),
            ]);
            return back();
            // pretend to save the mill
            // return Inertia::render('add-business', [
            //     'pageTitle' => 'Successfully Added Your Business',
                
            // ]);
        }
    }

    /**
     * We need edit(Mill $mill) if we allow submitting corrections to Mill data
     * It might be useful to make edit-business a separate page component...
     */
    public function edit(Mill $mill)
    {
        return Inertia::render('add-business', [
            'pageTitle' => 'Edit Mill',
            'mill' => $mill,
            ...$this->getData()
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

    /**
     * wrapper for fetching and bundling the data used by mill-map and mill-list
     */
    protected function getData(?Request $request = null): array
    {
        return [
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
            
            /**
             * Move millTypes and woodSpecies to HandleInertiaRequests::shareOnce() because they are used on multiple pages
             * and rarely, if ever, change.
             * Could probably do the same with millsApiUrl but hold off
             */

            // easy way to inform the front end of the api url
            'millsApiUrl' => route('api.v1.mills'),            
        ];
    }
}
