<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\MillRequest;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class MillCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class MillCrudController extends CrudController
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
        CRUD::setModel(\App\Models\Mill::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/mill');
        CRUD::setEntityNameStrings('mill', 'mills');

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
        $user = backpack_user();

        /**
         * Filter by state if $user has a state_id and isStateAgent()
         * if the request doesn't already have a filter for state_id, that is
         */
        if (!request()->has('state_id') && !empty($user->state_id) && $user->isStateAgent()) {
            // default to only show Mills from the StateAgent's state
            $this->crud->addClause('whereIn', 'state_id', [$user->state_id]);

            // CRUD::addButtonFromView('top', 'toggle-state', 'toggle-state-agent-filter', 'end');
        }

        if (! $this->crud->getRequest()->has('order')) {
            $this->crud->orderBy('mill_name', 'asc');
        }

        /**
         * Add filter for states...except filter is PRO add-on...
         */
        // $this->crud->addFilter([
        //     'name' => 'state_id',
        //     'type' => 'select2',
        //     'label' => 'Filter by State',
        // ], function () {
        //     return \App\Models\State::all()->pluck('name', 'id')->toArray();
        // }, function ($value) {
        //     $this->crud->addClause('where', 'state_id', $value);
        // });

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::column('mill_name')
            ->type('text')
            ->orderable(true);
        // match_id just cruds it up
        // CRUD::column([
        //     'name' => 'match_id',
        //     'label' => 'Match ID',
        // ])->type('text');
        CRUD::column('physical_address')
            ->type('text');
        CRUD::column('physical_city')
            ->type('text');
        CRUD::column('state_id')
            ->type('select')
            ->entity('state')
            ->model('App\Models\State')
            ->attribute('name')
            ->orderable(true);
        CRUD::column('physical_zip')
            ->type('text');
        CRUD::column('telephone')
            ->type('text');
        CRUD::column('email')
            ->type('email');
        CRUD::column('web_site')
            ->type('text');
        // that's all for now folks
        // the page width is filled
        // CRUD::column('fax')->type('text');
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(MillRequest::class);
        // CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        // this will be a read-only field that just shows the mill ID for reference
        // CRUD::field('id')
        //     ->type('text')
        //     ->attributes(['readonly' => 'readonly']);

        // the mill name should sit above the tabs for easy reference and should be editable from the main info tab
        CRUD::field([
            'name' => 'mill_name',
            'label' => 'Mill Name',
            'type' => 'text',
        ]); // ->tab('Basic Information');

        // basic info fields
        // match_id is a unique identifier that will be used to link mills to mill edits. It should be generated automatically and not editable by the user.
        CRUD::field([
            'name' => 'match_id',
            'label' => 'Match ID',
            'type' => 'text',
            'attributes' => [
                'readonly' => 'readonly',
                'disabled' => 'disabled',
            ],
        ])->tab('Basic Information');
        CRUD::field([
            'name' => 'year',
            'label' => 'Year Established',
            'type' => 'text',
        ])->tab('Basic Information');
        CRUD::field([
            'name' => 'size',
            'label' => 'Mill Size',
            'type' => 'text',
        ])->tab('Basic Information');

        // physical address fields
        CRUD::field([
            'name' => 'physical_address',
            'label' => 'Street Address',
            'type' => 'text',
        ])->tab('Physical Address');
        CRUD::field([
            'name' => 'physical_city',
            'label' => 'City',
            'type' => 'text',
        ])->tab('Physical Address');
        CRUD::field([
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ])->tab('Physical Address');
        CRUD::field([
            'name' => 'physical_zip',
            'label' => 'Zip Code',
            'type' => 'text',
        ])->tab('Physical Address');
        CRUD::field([
            'name' => 'latitude',
            'label' => 'Latitude',
            'type' => 'number',
            'attributes' => [
                'step' => 'any',
            ],
        ])->tab('Physical Address');
        CRUD::field([
            'name' => 'longitude',
            'label' => 'Longitude',
            'type' => 'number',
            'attributes' => [
                'step' => 'any',
            ],
        ])->tab('Physical Address');
        // omitting county_id for the time being because it really should be limited by state_id and that would require a custom field type

        // mailing address fields
        CRUD::field([
            'name' => 'mailing_address',
            'label' => 'Street Address',
            'type' => 'text',
        ])->tab('Mailing Address');
        CRUD::field([
            'name' => 'mailing_city',
            'label' => 'City',
            'type' => 'text',
        ])->tab('Mailing Address');
        CRUD::field([
            'name' => 'mailing_state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'mailingState',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ])->tab('Mailing Address');
        CRUD::field([
            'name' => 'mailing_zip',
            'label' => 'Zip Code',
            'type' => 'text',
        ])->tab('Mailing Address');
        // omitting county_id for the time being because it really should be limited by state_id and that would require a custom field type


        // contact info fields
        CRUD::field([
            'name' => 'telephone',
            'label' => 'Telephone',
            'type' => 'text',
        ])->tab('Contact Information');
        CRUD::field([
            'name' => 'fax',
            'label' => 'Fax',
            'type' => 'text',
        ])->tab('Contact Information');
        CRUD::field([
            'name' => 'email',
            'label' => 'Email',
            'type' => 'email',
        ])->tab('Contact Information');
        CRUD::field([
            'name' => 'web_site',
            'label' => 'Website',
            'type' => 'text',
        ])->tab('Contact Information');

        // relationships
        CRUD::field([
            /**
             * name = 'type' caused bad method exception
             * allegedly "name" should match the DB column name
             * however, mill_type is not a DB column 
             * further, the relationship method is millTypes (camel case & plural)
             */
            'name' => 'mill_type',
            'label' => 'Mill Type',
            'type' => 'select_multiple',
            'entity' => 'millTypes',
            'model' => 'App\Models\MillType',
            'attribute' => 'name',
            'multiple' => true,
            'pivot' => true,
        ])->tab('Relationships');
        CRUD::field([
            /**
             * name = 'wood_species' caused bad method exception
             * allegedly "name" should match the DB column name
             * however, woodSpecies is not a DB column
             * further, the apparent fix for millTypes was to change from "type" to "mill_type"
             * yet in this case, changing "name" to "woodSpecies" seemed to resolve the issue
             */
            'name' => 'woodSpecies',
            'label' => 'Wood Species',
            'type' => 'select_multiple',
            'entity' => 'woodSpecies',
            'model' => 'App\Models\WoodSpecies',
            'attribute' => 'name',
            'multiple' => true,
            'pivot' => true,
        ])->tab('Relationships');
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
