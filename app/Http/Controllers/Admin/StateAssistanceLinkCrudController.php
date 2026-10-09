<?php

namespace App\Http\Controllers\Admin;

use App\Models\State;
use App\Models\StateAssistanceCategory;
use App\Http\Requests\StateAssistanceLinkRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use App\Traits\SetsUpForStateAgents;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class StateAssistanceLinkCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StateAssistanceLinkCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use CrudPermissionTrait;
    use FiltersByState;
    use SetsUpForStateAgents;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\StateAssistanceLink::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state-assistance-link');
        CRUD::setEntityNameStrings('state assistance link', 'state assistance links');

        $this->setAccessUsingPermissions();
        $this->doSetupForStateAgent();
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // avoid N+1s from the state_name model_function column below
        $this->crud->query->with('category.state');

        if (! $this->crud->getRequest()->has('order')) {
            $this->crud->query
                ->orderBy(
                    StateAssistanceCategory::select('state_id')
                        ->whereColumn('state_assistance_categories.id', 'state_assistance_links.state_assistance_category_id'),
                    'asc'
                )
                ->orderBy(
                    StateAssistanceCategory::select('sort_weight')
                        ->whereColumn('state_assistance_categories.id', 'state_assistance_links.state_assistance_category_id'),
                    'asc'
                )
                
                ->orderBy('sort_weight', 'asc');
        }

        /**
         * Use FiltersByState trait to apply our filter and insert the widget!
         * The trait applies the filter using either its own applyStateFilterQuery()
         * or the composing class's overriding applyStateFilterQuery() method.
         */
        $this->doFilterByState();

        CRUD::column('state_name')
            ->label('State')
            ->type('model_function')
            ->function_name('stateName')
            ->orderable(true);
        CRUD::column('state_assistance_category_id')
            ->label('Category')
            ->type('select')
            ->entity('category')
            ->model('App\Models\StateAssistanceCategory')
            ->attribute('select_label')
            ->orderable(true);
        CRUD::column('label')
            ->type('text')
            ->orderable(true);
        CRUD::column('sort_weight')
            ->type('number')
            ->orderable(true);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StateAssistanceLinkRequest::class);

        CRUD::field([
            'name' => 'state_assistance_category_id',
            'label' => 'Category (State — Category)',
            'type' => 'select',
            'entity' => 'category',
            'model' => 'App\Models\StateAssistanceCategory',
            'attribute' => 'select_label',
            // preselected by the State Hub's Add link
            'default' => request('state_assistance_category_id'),
            // groups/orders options by state so the (already state-prefixed) labels
            // read as contiguous per-state blocks instead of being interleaved
            'options' => fn ($query) => $query->orderBy('state_id', 'asc')->orderBy('sort_weight', 'asc')->get(),
        ]);
        CRUD::field([
            'name' => 'label',
            'label' => 'Label',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
        ]);
        CRUD::field([
            'name' => 'url',
            'label' => 'URL',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'sort_weight',
            'label' => 'Sort Weight',
            'type' => 'number',
            'default' => 10,
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * Adds a subquery to allow filtering by state_id on the related StateAssistanceCategory model.
     * Overrides the trait method from FiltersByState.
     * 
     * @param Builder $query
     * @return Builder
     */
    public function applyStateFilterQuery(Builder $query): Builder
    {
        return $this->crud->addClause('whereHas', 'category', function ($query) {
            /**
             * I didn't think we could reliably use $this inside "this" Closure, but apparently we can!
             */
            $query->where('state_id', $this->getStateFilterValue());
        });
    }

    /**
     * Overrides SetsUpForStateAgents: links belong to a state via their category.
     */
    protected function scopeToAgentState(Builder $query, int $stateId): void
    {
        $query->whereHas('category', fn ($query) => $query->where('state_id', $stateId));
    }

    /**
     * Overrides SetsUpForStateAgents: only offer the agent's state's categories.
     */
    protected function limitAgentStateField(int $stateId): void
    {
        CRUD::field('state_assistance_category_id')
            ->options(fn ($query) => $query->where('state_id', $stateId)->orderBy('sort_weight', 'asc')->get());
    }

    /**
     * Overrides SetsUpForStateAgents: there's no state_id to coerce, so reject another state's category instead.
     * The form only offers the agent's categories, so this only trips on a tampered request.
     */
    protected function enforceAgentStateOnInput(int $stateId): void
    {
        $categoryId = CRUD::getRequest()->input('state_assistance_category_id');

        abort_if(
            $categoryId !== null
                && ! StateAssistanceCategory::whereKey($categoryId)->where('state_id', $stateId)->exists(),
            403
        );
    }

}
