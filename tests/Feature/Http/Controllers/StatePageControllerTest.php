<?php

use App\Models\State;
use App\Models\StateAssistanceCategory;
use App\Models\StateAssistanceLink;
use App\Models\StateContact;
use App\Models\StateEconomicImpact;
use App\Models\StateForestOverview;
use App\Models\StateForestProduct;
use App\Models\StateForestryAgency;
use App\Models\StateForestType;
use App\Models\StatePage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function namedState(string $name, string $abbreviation): State
{
    return State::factory()->create(['name' => $name, 'abbreviation' => $abbreviation, 'slug' => Str::slug($name)]);
}

/**
 * A state with every section of its public page filled in.
 */
function fullyBuiltState(): State
{
    $state = namedState('Georgia', 'GA');

    StatePage::factory()->for($state)->create(['hero_copy' => '<ul><li>Georgia grows trees.</li></ul>']);
    StateContact::factory()->count(2)->for($state)->create();
    StateForestOverview::factory()->for($state)->create();
    StateForestType::factory()->count(2)->for($state)->create();
    StateForestProduct::factory()->count(3)->for($state)->create();
    StateEconomicImpact::factory()->for($state)->create();
    StateForestryAgency::factory()->for($state)->create();

    $category = StateAssistanceCategory::factory()->for($state)->create();
    StateAssistanceLink::factory()->count(2)->for($category, 'category')->create();

    return $state;
}

describe('index', function () {
    test('it lists southern states that have a page, alphabetically', function () {
        foreach ([['Georgia', 'GA'], ['Alabama', 'AL'], ['Texas', 'TX']] as [$name, $abbreviation]) {
            StatePage::factory()->for(namedState($name, $abbreviation))->create();
        }

        $this->get('/states')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('states')
                ->where('states.0.name', 'Alabama')
                ->where('states.1.name', 'Georgia')
                ->where('states.2.name', 'Texas')
                ->has('states', 3)
            );
    });

    test('it leaves out states without a page, and states outside the South', function () {
        namedState('Florida', 'FL'); // no page yet
        StatePage::factory()->for(namedState('Ohio', 'OH'))->create();

        $this->get('/states')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('states', 0));
    });
});

describe('show', function () {
    test('it sends every section of the page', function () {
        $state = fullyBuiltState();

        $this->get("/states/{$state->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('state-page')
                ->where('pageTitle', 'Georgia Forest Products')
                ->where('state.name', 'Georgia')
                ->has('state.state_page')
                ->has('state.state_contacts', 2)
                ->has('state.state_forest_overview')
                ->has('state.state_forest_types', 2)
                ->has('state.state_forest_products', 3)
                ->has('state.state_economic_impact')
                ->has('state.state_forestry_agency.assistance_categories', 1)
                ->has('state.state_forestry_agency.assistance_categories.0.links', 2)
            );
    });

    test('the description comes from the hero copy, without markup', function () {
        $state = fullyBuiltState();

        $this->get("/states/{$state->slug}")
            ->assertInertia(fn (Assert $page) => $page->where('pageSeo.description', 'Georgia grows trees.'));
    });

    test('a long hero copy is trimmed for the description', function () {
        $state = namedState('Alabama', 'AL');
        StatePage::factory()->for($state)->create(['hero_copy' => '<p>'.str_repeat('Timber! ', 40).'</p>']);

        $this->get("/states/{$state->slug}")
            ->assertInertia(fn (Assert $page) => $page->where(
                'pageSeo.description',
                fn (string $description) => mb_strlen($description) <= 163 && ! str_contains($description, '<p>')
            ));
    });

    test('a state with nothing filled in still renders, with a generic description', function () {
        $state = namedState('Alabama', 'AL');

        $this->get("/states/{$state->slug}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('pageSeo.description', 'Forest industry information, contacts, and resources for Alabama.')
                ->where('state.state_page', null)
                ->has('state.state_contacts', 0)
            );
    });

    test('an unknown state 404s', function () {
        $this->get('/states/atlantis')->assertNotFound();
    });
});
