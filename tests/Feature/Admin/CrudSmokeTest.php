<?php

use App\Models\County;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\Mill;
use App\Models\MillType;
use App\Models\PageSeo;
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
use Illuminate\Support\Facades\Route;
use App\Models\WoodSpecies;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Every admin CRUD screen renders for a user with that table's .edit permission: list data, create, show and edit.
 * Catches broken column/field config (e.g., a column pointing at a renamed relationship), and covers each
 * CrudController's setup*Operation() methods along the way.
 *
 * Access rules themselves are tested in StateAgentAccessTest.
 */

beforeEach(function () {
    // several state-section factories pick an existing State (State::inRandomOrder()) rather than making one
    $this->state = State::factory()->create();
});

/**
 * StateAssistanceLinkFactory picks an existing category (inRandomOrder()) before any override applies, so one has to
 * exist first.
 */
function assistanceLinkIn(State $state): StateAssistanceLink
{
    $category = StateAssistanceCategory::factory()->for($state)->create();

    return StateAssistanceLink::factory()->for($category, 'category')->create();
}

test('the :dataset screens render', function (string $segment, string $table, Closure $makeRecord) {
    loginAdminUser(["{$table}.edit"]);
    $record = $makeRecord($this->state);

    $rows = listRows($this, $segment);
    expect($rows['recordsTotal'])->toBeGreaterThanOrEqual(1);

    $this->get("/admin/{$segment}/create")->assertOk();
    // PermissionManager's role/permission screens have no show page
    if (Route::has("admin.{$segment}.show")) {
        $this->get("/admin/{$segment}/{$record->getKey()}/show")->assertOk();
    }
    $this->get("/admin/{$segment}/{$record->getKey()}/edit")->assertOk();
})->with([
    'mill' => ['mill', 'mills', fn (State $state) => Mill::factory()->for($state)->hasAttached(MillType::factory())->create()],
    'mill type' => ['mill-type', 'mill_types', fn () => MillType::factory()->create()],
    'wood species' => ['wood-species', 'wood_species', fn () => WoodSpecies::factory()->create()],
    'state' => ['state', 'states', fn (State $state) => $state],
    'county' => ['county', 'counties', fn (State $state) => County::forceCreate(['name' => 'Clarke', 'type' => 'county', 'state_id' => $state->id])],
    'FAQ category' => ['faq-category', 'faq_categories', fn () => FaqCategory::factory()->create()],
    'FAQ' => ['faq', 'faqs', fn () => Faq::factory()->for(FaqCategory::factory())->create()],
    'page SEO' => ['page-seo', 'page_seos', fn () => PageSeo::factory()->create()],
    'state page' => ['state-page', 'state_pages', fn (State $state) => StatePage::factory()->for($state)->create()],
    'state contact' => ['state-contact', 'state_contacts', fn (State $state) => StateContact::factory()->for($state)->create()],
    'forest overview' => ['state-forest-overview', 'state_forest_overviews', fn (State $state) => StateForestOverview::factory()->for($state)->create()],
    'forest type' => ['state-forest-type', 'state_forest_types', fn (State $state) => StateForestType::factory()->for($state)->create()],
    'forest product' => ['state-forest-product', 'state_forest_products', fn (State $state) => StateForestProduct::factory()->for($state)->create()],
    'economic impact' => ['state-economic-impact', 'state_economic_impacts', fn (State $state) => StateEconomicImpact::factory()->for($state)->create()],
    'forestry agency' => ['state-forestry-agency', 'state_forestry_agencies', fn (State $state) => StateForestryAgency::factory()->for($state)->create()],
    'assistance category' => ['state-assistance-category', 'state_assistance_categories', fn (State $state) => StateAssistanceCategory::factory()->for($state)->create()],
    'assistance link' => ['state-assistance-link', 'state_assistance_links', fn (State $state) => assistanceLinkIn($state)],
    // only Superadmins may edit other users, so use the logged-in user's own record
    'user' => ['user', 'users', fn () => backpack_user()],
    'role' => ['role', 'roles', fn () => Role::findOrCreate('Smoke Test Role', 'web')],
    'permission' => ['permission', 'permissions', fn () => Permission::findOrCreate('smoke_tests.edit', 'web')],
]);

test('the statistics pages render', function (string $path) {
    loginAdminUser([]);

    $this->get("/admin/{$path}")->assertOk();
})->with([
    'overview' => 'statistics',
    'updated' => 'statistics/updated',
    'additions' => 'statistics/additions',
]);

test('the state hub renders for a state with every section filled in', function () {
    loginAdminUser(['state_pages.edit', 'state_contacts.edit', 'state_assistance_links.edit']);

    StatePage::factory()->for($this->state)->create();
    StateContact::factory()->for($this->state)->create();
    StateForestOverview::factory()->for($this->state)->create();
    StateForestType::factory()->for($this->state)->create();
    StateForestProduct::factory()->for($this->state)->create();
    StateEconomicImpact::factory()->for($this->state)->create();
    StateForestryAgency::factory()->for($this->state)->create();
    assistanceLinkIn($this->state);

    $this->get("/admin/state-hub/{$this->state->id}")->assertOk()->assertSee($this->state->name);
});

test('the state hub picker renders', function () {
    loginAdminUser(['state_pages.edit']);

    $this->get('/admin/state-hub')->assertOk();
});
