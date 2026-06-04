<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        // check permissions
        /**
         * As with other permission schemas, prolly best to denyAll at first, then allow as needed.
         * OOH, there's even a denyAllAccess() method!
         */
        // CRUD::denyAccess(['list', 'show', 'create', 'update', 'delete']);
        CRUD::denyAllAccess();

        /**
         * The docs say that access and permissions should be used separately.
         * However, they seem too intertwined for that to be possible.
         * When we allow or deny access, aside from user-owned items (only users, in our system), what else could govern access
         * except permissions?
         * I suppose our system is spozta assigns permissions based on state, as State Agents are spozta have permission to approve mill
         * submissions and edits for mills in their states.
         * The docs also propose a very simple "permission level" schema
         * $table.$level indicates list of allowed CRUD ops.
         * e.g., 
         *      users.edit (where 'edit' allows all ops)
         *      users.see (where 'see' only allows 'list' & 'show')
         * However, now that I've started to embrace that paradigm, I recall that some of our models have additional operations.
         * Most notably, 'approve' WRT Mills (both new submissions and user submitted MillEdits).
         * Also, there's the whole import from spreadsheet beeswax.
         * Perhaps we need to define CRUD ops and permission levels on each Model?
         * That seems like it would still allow using the CrudPermissionTrait we just created.
         * Oh well, something for the morrow...
         * 
         * The nonsense below is exactly what the CrudPermissionsTrait does.
         */
        if (backpack_user()->can('users.edit')) {
            // DENY ALL
            // list == viewAny
            // show == view
            CRUD::allowAccess(['list', 'show', 'create', 'update', 'delete']);
        } else if (backpack_user()->can('users.see')) {
            CRUD::allowAccess(['list', 'show']);
        }

        /**
         * FYI, permissionmanager configs use the BackpackUser model instead of the default Laravel User model as we do below.
         * Actually, that's incorrect.
         * permissionmanager pulls the config from backpack.base.users_model_fqn, which in turns pulls it from auth.providers.users.model
         */
        CRUD::setModel(\App\Models\User::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        CRUD::setEntityNameStrings('user', 'users');

        /**
         * CrudPermissionsTrait goes here because the model needs to be set before it can check it programmatically.
         */
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
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        // CRUD::column('name');
        // CRUD::column('email');

        // alternatively, pass an array to setColumns()
        CRUD::setColumns(['name', 'email']);
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        // CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        CRUD::field('name')->validationRules('required|min:5');
        CRUD::field('email')->validationRules('required|email|unique:users,email');
        CRUD::field('password')->validationRules('required');

        /**
         * Laravel 10+ hashes passwords out of the box.
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
        // $this->setupCreateOperation();

        // same
        CRUD::field('name')->validationRules('required|min:5');
        // need to pass ID to the unique rule on update to sidestep the unique constraint
        CRUD::field('email')->validationRules('required|email|unique:users,email,'.CRUD::getCurrentEntryId());
        // only needed if you want to change the password
        CRUD::field('password')->hint('Type a password to change it.');

        /**
         * Laravel 10+ handles password hashing for you.
         */
    }
}
