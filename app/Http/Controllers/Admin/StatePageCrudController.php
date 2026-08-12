<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\StatePageRequest;
use App\Traits\CrudPermissionTrait;
use App\Traits\FiltersByState;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class StatePageCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class StatePageCrudController extends CrudController
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
        CRUD::setModel(\App\Models\StatePage::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/state-page');
        CRUD::setEntityNameStrings('state page', 'state pages');

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
         * states.name, bruh
         * we can actually just order by state_id because they end up being alphabetical anyway
         */
        if (! $this->crud->getRequest()->has('order')) {
            $this->crud->orderBy('state_id', 'asc');
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
        CRUD::column('hero_headline')
            ->type('text')
            ->orderable(true);
        CRUD::column('contacts_headline')
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
        CRUD::setValidation(StatePageRequest::class);

        CRUD::field([
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
        ]);
        CRUD::field([
            'name' => 'hero_headline',
            'label' => 'Hero Headline',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'hero_img_dt',
            'label' => 'Hero Image (Desktop)',
            'type' => 'upload',
            'disk' => 'public',
            'withFiles' => true,
        ]);
        CRUD::field([
            'name' => 'hero_img_mobile',
            'label' => 'Hero Image (Mobile)',
            'type' => 'upload',
            'disk' => 'public',
            'withFiles' => true,
        ]);
        CRUD::field([
            'name' => 'hero_copy',
            'label' => 'Hero Copy',
            'type' => 'ckeditor',
        ]);
        CRUD::field([
            'name' => 'contacts_headline',
            'label' => 'Contacts Headline',
            'type' => 'text',
        ]);
        CRUD::field([
            'name' => 'contacts_copy',
            'label' => 'Contacts Copy',
            'type' => 'textarea',
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
     * In order to have associated files deleted when the underlying record is deleted, we have to set up the DeleteOperation and inform of the files fields to delete.
     */
    protected function setupDeleteOperation(): void
    {
        CRUD::field('hero_image_dt')
            ->type('upload')
            ->withFiles();
        CRUD::field('hero_image_mobile')
            ->type('upload')
            ->withFiles();
    }
}
