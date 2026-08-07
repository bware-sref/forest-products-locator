<?php

namespace App\Http\Controllers;

use App\Models\State;
use Illuminate\Support\Str;
use Inertia\Inertia;

class StatePageController extends Controller
{
    /**
     * List southern states that have a page, linking through to each one's show page.
     */
    public function index()
    {
        $states = State::whereIn('abbreviation', State::$southernStates)
            ->has('statePage')
            ->orderBy('name')
            ->get();

        return Inertia::render('states', [
            'pageTitle' => 'State Pages',
            'pageSeo' => [
                'title' => 'State Pages',
                'description' => 'Forest industry information, contacts, and resources organized by state.',
            ],
            'states' => $states,
        ]);
    }

    /**
     * Show the full per-state page: hero, contacts, forest overview, economic
     * impact, and forestry agency/assistance sections.
     * Everything but statePage hangs directly off state_id (see State model),
     * so it's all loaded here rather than nested under statePage.
     */
    public function show(State $state)
    {
        $state->load([
            'statePage',
            'stateContacts',
            'stateForestOverview',
            'stateForestTypes',
            'stateForestProducts',
            'stateEconomicImpact',
            'stateForestryAgency.assistanceCategories.links',
        ]);

        $description = $state->statePage?->hero_copy
            ? Str::limit(strip_tags($state->statePage->hero_copy), 160)
            : "Forest industry information, contacts, and resources for {$state->name}.";

        return Inertia::render('state-page', [
            'pageTitle' => $state->name.' Forest Products',
            'pageSeo' => [
                'title' => $state->name.' Forest Products',
                'description' => $description,
            ],
            'state' => $state,
        ]);
    }
}
