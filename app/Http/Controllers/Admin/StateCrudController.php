<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StateRequest;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StateCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StateCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use CrudPermissionTrait;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\State::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state');
        CRUD::setEntityNameStrings('state', 'states');

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
        // CRUD::setFromDb(); // set columns from db columns.

        /**
         * order by state name if there isn't already an order parameter in the request
         */
        if (! $this->crud->getRequest()->has('order')) {
            $this->crud->orderBy('name', 'asc');
        }

        /**
         * only show states that have mills!
         */
        if (! $this->crud->getRequest()->has('all')) {
            $this->crud->addClause('whereHas', 'mills');
        }

        CRUD::addButtonFromView('top', 'only-mill-states', 'only-mill-states');

        /**
         * Add counting the mills to the query
         */
        $this->crud->query->withCount('mills');

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::column('name')
            ->type('text')
            ->orderable(true);

        /**
         * Instead of relationship_count type, use the fake field created by withCount()
         */
        CRUD::column('mills_count')
            // ->type('relationship_count')
            ->after('name')
            ->type('text')
            ->label('Mills')
            ->suffix(' mills')
            ->orderable(true);

        CRUD::column('resource_summary')
            ->type('text')
            ->label('Summary');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(StateRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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
