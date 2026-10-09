<?php

namespace App\Http\Controllers\Admin;

use App\Models\State;
use Illuminate\Routing\Controller;

/**
 * One page per State listing every State Page section, with links into the existing CRUDs.
 *
 * This page doesn't edit anything, so it adds no permission logic of its own beyond deciding what to show:
 * every link lands in a CRUD which already enforces permissions (CrudPermissionTrait) and StateAgent
 * restrictions (SetsUpForStateAgents).
 */
class StateHubController extends Controller
{
    /**
     * Sections in front-end order.
     * table: permission prefix ({table}.see / {table}.edit), also used to look up the CRUD segment below
     * relation: State relation holding the section's record(s)
     * single: one record per state (see SetsUpForStateAgents::canBeOnlyOne)
     * label: attribute shown for each record
     */
    public const array SECTIONS = [
        [
            'title' => 'Hero / Page Content',
            'table' => 'state_pages',
            'relation' => 'statePage',
            'single' => true,
            'label' => 'hero_headline',
        ],
        [
            'title' => 'Contacts',
            'table' => 'state_contacts',
            'relation' => 'stateContacts',
            'single' => false,
            'label' => 'name',
        ],
        [
            'title' => 'Forest Overview',
            'table' => 'state_forest_overviews',
            'relation' => 'stateForestOverview',
            'single' => true,
            'label' => 'headline',
        ],
        [
            'title' => 'Regional Forest Types',
            'table' => 'state_forest_types',
            'relation' => 'stateForestTypes',
            'single' => false,
            'label' => 'title',
        ],
        [
            'title' => 'Forest Products',
            'table' => 'state_forest_products',
            'relation' => 'stateForestProducts',
            'single' => false,
            'label' => 'label',
        ],
        [
            'title' => 'Economic Impact',
            'table' => 'state_economic_impacts',
            'relation' => 'stateEconomicImpact',
            'single' => true,
            'label' => 'headline',
        ],
        [
            'title' => 'Forestry Agency',
            'table' => 'state_forestry_agencies',
            'relation' => 'stateForestryAgency',
            'single' => true,
            'label' => 'headline',
        ],
        [
            'title' => 'Assistance Categories',
            'table' => 'state_assistance_categories',
            'relation' => 'stateAssistanceCategories',
            'single' => false,
            'label' => 'title',
        ],
    ];

    /**
     * CRUD route segments, keyed by table (matches routes/backpack/custom.php).
     */
    public const array SEGMENTS = [
        'state_pages' => 'state-page',
        'state_contacts' => 'state-contact',
        'state_forest_overviews' => 'state-forest-overview',
        'state_forest_types' => 'state-forest-type',
        'state_forest_products' => 'state-forest-product',
        'state_economic_impacts' => 'state-economic-impact',
        'state_forestry_agencies' => 'state-forestry-agency',
        'state_assistance_categories' => 'state-assistance-category',
        'state_assistance_links' => 'state-assistance-link',
    ];

    public function show(?State $state = null)
    {
        $user = backpack_user();

        abort_unless($user->canAny(self::permissions()), 403);

        /**
         * StateAgents only ever see their own state, whatever's in the URL.
         */
        if ($user->isStateAgent() && $state?->id !== (int) $user->state_id) {
            return redirect()->route('admin.page.state-hub.show', ['state' => $user->state_id]);
        }

        /**
         * Everyone else picks a state first.
         */
        if (!$state) {
            return view('admin.state-hub.index', [
                'title' => 'State Hub',
                'breadcrumbs' => [
                    trans('backpack::crud.admin') => backpack_url('dashboard'),
                    'State Hub' => false,
                ],
                'states' => State::has('mills')->orderBy('name')->get(),
            ]);
        }

        $state->load(array_merge(
            array_column(self::SECTIONS, 'relation'),
            ['stateAssistanceCategories.links'],
        ));

        return view('admin.state-hub.show', [
            'title' => "{$state->name} :: State Hub",
            'breadcrumbs' => array_filter([
                trans('backpack::crud.admin') => backpack_url('dashboard'),
                // agents can't pick another state, so don't offer the picker
                'State Hub' => $user->isStateAgent() ? null : route('admin.page.state-hub.show'),
                $state->name => false,
            ], fn($url) => $url !== null),
            'state' => $state,
            'sections' => self::SECTIONS,
            'segments' => self::SEGMENTS,
            'isStateAgent' => $user->isStateAgent(),
            'hubUrl' => route('admin.page.state-hub.show', ['state' => $state]),
        ]);
    }

    /**
     * Every permission which grants access to at least one section.
     */
    public static function permissions(): array
    {
        return collect(self::SEGMENTS)
            ->keys()
            ->flatMap(fn($table) => ["{$table}.see", "{$table}.edit"])
            ->all();
    }
}
