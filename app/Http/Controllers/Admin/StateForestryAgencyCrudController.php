<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StateForestryAgencyRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StateForestryAgencyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StateForestryAgencyCrudController extends CrudController
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
        CRUD::setModel(\App\Models\StateForestryAgency::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state-forestry-agency');
        CRUD::setEntityNameStrings('forestry agency section', 'forestry agency sections');

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
            $this->crud->query->orderBy('state_id', 'asc');
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
        CRUD::setValidation(StateForestryAgencyRequest::class);

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
            'label' => 'Agency Name / Headline',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'body',
            'label' => 'Body',
            'type' => 'ckeditor',
        ]);
        CRUD::field([
            'name' => 'cta_1_label',
            'label' => 'CTA 1 Label',
            'type' => 'text',
            'tab' => 'Calls to Action',
        ]);
        CRUD::field([
            'name' => 'cta_1_url',
            'label' => 'CTA 1 URL',
            'type' => 'text',
            'tab' => 'Calls to Action',
        ]);
        CRUD::field([
            'name' => 'cta_2_label',
            'label' => 'CTA 2 Label',
            'type' => 'text',
            'tab' => 'Calls to Action',
        ]);
        CRUD::field([
            'name' => 'cta_2_url',
            'label' => 'CTA 2 URL',
            'type' => 'text',
            'tab' => 'Calls to Action',
        ]);
        CRUD::field([
            'name' => 'assistance_headline',
            'label' => 'Assistance Headline',
            'type' => 'text',
            'tab' => 'Available Assistance',
        ]);
        CRUD::field([
            'name' => 'assistance_copy',
            'label' => 'Assistance Copy',
            'type' => 'textarea',
            'tab' => 'Available Assistance',
            'hint' => 'Categories and links for this section are managed under Assistance Categories.',
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
