<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StateEconomicImpactRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StateEconomicImpactCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StateEconomicImpactCrudController extends CrudController
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
        CRUD::setModel(\App\Models\StateEconomicImpact::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state-economic-impact');
        CRUD::setEntityNameStrings('economic impact section', 'economic impact sections');

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
        if (! $this->crud->getRequest()->has('order')) {
            $this->crud->query
                ->orderBy('state_id', 'asc');
        }
        
        /**
         * DIY filter
         */
        $this->doFilterByState();

        CRUD::column('state_id')
            ->type('select')
            ->entity('state')
            ->model('App\Models\State')
            ->attribute('name')
            ->orderable(true);
        CRUD::column('headline')
            ->type('text')
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
        CRUD::setValidation(StateEconomicImpactRequest::class);

        CRUD::field([
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ]);
        CRUD::field([
            'name' => 'headline',
            'label' => 'Headline',
            'type' => 'text',
        ]);

        foreach (range(1, 3) as $i) {
            CRUD::field([
                'name' => "stat_{$i}_label",
                'label' => "Stat {$i} Label",
                'type' => 'text',
                'tab' => 'Stats',
            ]);
            CRUD::field([
                'name' => "stat_{$i}_value",
                'label' => "Stat {$i} Value",
                'type' => 'text',
                'tab' => 'Stats',
            ]);
        }
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
