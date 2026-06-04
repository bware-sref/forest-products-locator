<?php

namespace App\Http\Controllers;

use App\Models\State;
use App\Models\StateResource;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StateResourceController extends Controller
{
    /**
     * StateResource index is slightly different than other indices 
     * because instead of showing state resources, it shows States with resources and links to each States
     * byState() page.
     */
    public function index()
    {
        /**
         * Maybe need a scope for StateResources similar to Mills?
         * Only pull published by default.
         */
        $states = State::select(['id', 'name', 'abbreviation', 'resource_summary'])
            ->has('stateResources')
            ->orderBy('name')
            ->get();
        return Inertia::render('state-resources', [
            'pageTitle' => 'State Resources',
            'states' => $states,
        ]);
    }

    /**
     * Fetch & display resources for the given state.
     * On the front, the URLs include the state name (or abbreviation)
     */
    public function byState(State $state)
    {}

    /**
     * Fetch & display a given StateResource
     */
    public function show(State $state, StateResource $stateResource)
    {}
}
