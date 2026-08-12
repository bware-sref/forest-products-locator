<?php
/**
 * I don't know if this even used anywhere/more.
 */
namespace App\Http\Controllers\Admin;

use App\Http\Requests\AgentRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class AgentCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class AgentCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Agent::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/agent');
        CRUD::setEntityNameStrings('agent', 'agents');

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

        $this->doFilterByState();

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::column('first_name')->type('text');
        CRUD::column('last_name')->type('text');
        CRUD::column('title')->type('text');
        CRUD::column('email')->type('email');
        CRUD::column('phone')->type('text');
        CRUD::column('street_address')->type('text');
        CRUD::column('city')->type('text');
        CRUD::column('state_id')
            ->type('select')
            ->entity('state')
            ->model('App\Models\State')
            ->attribute('name');
        CRUD::column('zip_code')->type('text');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(AgentRequest::class);
        // CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        CRUD::field('first_name')->type('text');
        CRUD::field('last_name')->type('text');
        CRUD::field('title')->type('text');
        CRUD::field('email')->type('email');
        CRUD::field('phone')->type('text');
        CRUD::field('street_address')->type('text');
        CRUD::field('city')->type('text');
        CRUD::field('state_id')
            ->type('select')
            ->entity('state')
            ->model('App\Models\State')
            ->attribute('name');
        CRUD::field('zip_code')->type('text');
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
