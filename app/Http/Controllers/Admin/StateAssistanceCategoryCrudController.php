<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StateAssistanceCategoryRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Database\Eloquent\Builder;


/**
 * Class StateAssistanceCategoryCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StateAssistanceCategoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use CrudPermissionTrait;
    use FiltersByState;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\StateAssistanceCategory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state-assistance-category');
        CRUD::setEntityNameStrings('state assistance category', 'state assistance categories');

        $this->setAccessUsingPermissions();
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        /**
         * Only apply this ordering if the request doesn't already have an order specified
         */
        if (! $this->crud->getRequest()->has('order')) {
            /**
             * apply directly to query so we can multisort
             */
            $this->crud->query
                ->orderBy('state_id', 'asc')
                ->orderBy('sort_weight', 'asc');
        }

        // State filter, yo!
        $this->doFilterByState();

        CRUD::column('state_id')
            ->type('select')
            ->entity('state')
            ->model('App\Models\State')
            ->attribute('name')
            ->orderable(true);
        CRUD::column('title')
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
        CRUD::setValidation(StateAssistanceCategoryRequest::class);

        CRUD::field([
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ]);
        CRUD::field([
            'name' => 'title',
            'label' => 'Title',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'description',
            'label' => 'Description',
            'type' => 'textarea',
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
}
