<?php

namespace App\Http\Controllers;

use App\Models\PageSeo;
use App\Models\State;
use App\Models\StateResource;
use Illuminate\Support\Str;
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
        $states = State::select(['id', 'name', 'abbreviation', 'slug', 'resource_summary'])
            ->has('stateResources')
            ->orderBy('name')
            ->get();

        return Inertia::render('state-resources', [
            'pageTitle' => 'State Resources',
            'pageSeo' => PageSeo::resolve(
                'state-resources',
                'State Resources',
                'Find forestry resources and contacts organized by state.'
            ),
            'states' => $states,
        ]);
    }

    /**
     * Fetch & display resources for the given state.
     * On the front, the URLs include the state name (or abbreviation)
     */
    public function byState(State $state)
    {
        /**
         * load resources for this state
         */
        $state->load('stateResources');

        return Inertia::render('state-resources-list', [
            'pageTitle' => $state->name.' Resources',
            'pageSeo' => [
                'title' => $state->name.' Resources',
                'description' => "Forestry resources and contacts for {$state->name}.",
            ],
            'state' => $state,
        ]);
    }

    /**
     * Fetch & display a given StateResource
     * URL needs state abbreviation and the stateResource id
     */
    public function show(State $state, StateResource $stateResource)
    {
        $description = $stateResource->teaser
            ? Str::limit(strip_tags($stateResource->teaser), 160)
            : "{$stateResource->title} — a {$state->name} forestry resource.";

        return Inertia::render('state-resources-show', [
            'pageTitle' => $stateResource->title.' : '.$state->name.' Resources',
            'pageSeo' => [
                'title' => $stateResource->title.' | '.$state->name.' Resources',
                'description' => $description,
            ],
            'resource' => $stateResource,
            'state' => $state,
        ]);
    }
}
