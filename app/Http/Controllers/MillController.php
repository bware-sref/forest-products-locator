<?php

namespace App\Http\Controllers;

use App\Exports\MillsExport;
use App\Http\Requests\StoreMillRequest;
use App\Http\Requests\UpdateMillRequest;
use App\Http\Requests\MillResourceRequest;
use App\Models\County;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\State;
use App\Models\WoodSpecies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

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
        return Inertia::render('mill-single', [
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
        /**
         * if the request validates, we need to flesh out the data before storing it
         * flesh out means:
         *  - poll amazon to lookup lat & long
         *  - lookup county? maybe, depends on what comes back from the geocode request
         *  - add the submitter's IP to the data
         * since "flesh out" includes an external API call, we should probably dispatch a job to handle it and just store the validated data for now (whoa, quoth the Neo).
         */
        // $data = $request->validated();
        $data = $request->all();
        Log::debug('Mills::store() request->all() data', $data);

        /**
         * I don't think we need to wrap all this in a conditional because if the validation fails, it will automatically redirect back with errors and old input, so we won't even get to this point if the data is invalid.
         */

        try {

            /**
             * Let's use a transaction here because we need to ensure that the mill record is created before we can attach the mill types and wood species, and we don't want to end up with orphaned records if something goes wrong.
             */
            DB::transaction(function () use ($data) {
                /**
                 * we need to handle the mill types and wood species separately because they are many-to-many relationships and require the mill record to be created first so we can get its ID for the pivot tables.
                 */
                /**
                 * peel off mill_types and wood_species from the data and handle them separately after the mill record is created.
                 */
                $millTypeIds = $data['mill_types'] ?? [];
                $woodSpeciesIds = $data['wood_species'] ?? [];
                unset(
                    $data['mill_types'],
                    $data['wood_species'],
                    $data['mailing_address_same_as_physical'], // this is only used for mutating the data and doesn't need to be stored
                );

                Log::debug('Creating mill with data', $data);

                $newMill = Mill::create($data);

                // attach mill types and wood species
                if (!empty($millTypeIds)) {
                    $newMill->millTypes()->attach($millTypeIds);
                }
                if (!empty($woodSpeciesIds)) {
                    $newMill->woodSpecies()->attach($woodSpeciesIds);
                }

                $msg = sprintf('Successfully submitted "%s" (Mill #%d!)', $newMill->mill_name, $newMill->id);
                Log::debug($msg, ['millData' => $newMill->toArray()]);
                Inertia::flash([
                    'type' => 'success',
                    'message' => $msg,
                ]);
            }, attempts: 3);
        } catch (\Exception $e) {
            Log::error('Error creating mill', ['error' => $e->getMessage()]);
            Inertia::flash([
                'type' => 'error',
                'message' => 'An error occurred while submitting your Mill. Please try again.',
            ]);
        }

        // Inertia::flash($flash);
        return to_route('add-business');        
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

    public function export(MillResourceRequest $request)
    {
        /**
         * increase maximum execution time for this request to 5 minutes
         * we of course don't want it to take that long
         * in fact, we should probably pre-create and cache the full export via the job queue so it will load fastly
         */
        set_time_limit(300);
        
        $validated = $request->validated();
        Log::debug('request params for export', ['validated' => $validated]);
        $mills = Mill::apiSearch($validated);
        Log::debug('exporting mills...', ['count' => count($mills)]);
        return Excel::download(new MillsExport($mills), 'mills.xlsx');
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
