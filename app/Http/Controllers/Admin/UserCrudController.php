<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRoles;
use App\Traits\CrudPermissionTrait;
use App\Http\Requests\StoreUserRequest as StoreRequest;
use App\Http\Requests\UpdateUserRequest as UpdateRequest;
// use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Backpack\PermissionManager\app\Http\Controllers\UserCrudController as BaseUserCrudController;
use Illuminate\Support\Facades\Log;
use Override;

/**
 * Class UserCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class UserCrudController extends BaseUserCrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation { store as traitStore; }
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation { update as traitUpdate; }
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
        // check permissions
        /**
         * As with other permission schemas, prolly best to denyAll at first, then allow as needed.
         * OOH, there's even a denyAllAccess() method!
         */
        // CRUD::denyAccess(['list', 'show', 'create', 'update', 'delete']);
        // CRUD::denyAllAccess();

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
        // if (backpack_user()->can('users.edit')) {
        //     // DENY ALL
        //     // list == viewAny
        //     // show == view
        //     CRUD::allowAccess(['list', 'show', 'create', 'update', 'delete']);
        // } else if (backpack_user()->can('users.see')) {
        //     CRUD::allowAccess(['list', 'show']);
        // }

        /**
         * FYI, permissionmanager configs use the BackpackUser model instead of the default Laravel User model as we do below.
         * Actually, that's incorrect.
         * permissionmanager pulls the config from backpack.base.users_model_fqn, which in turns pulls it from auth.providers.users.model
         */
        // CRUD::setModel(\App\Models\User::class);
        // CRUD::setRoute(config('backpack.base.route_prefix') . '/user');
        // CRUD::setEntityNameStrings('user', 'users');

        parent::setup();

        /**
         * CrudPermissionsTrait goes here because the model needs to be set before it can check it programmatically.
         */
        $this->setAccessUsingPermissions();
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    public function setupListOperation()
    {
        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        // CRUD::column('name');
        // CRUD::column('email');

        // alternatively, pass an array to setColumns()
        // CRUD::setColumns(['name', 'email']);
        parent::setupListOperation();
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    public function setupCreateOperation()
    {
        // CRUD::field('name')->validationRules('required|min:5');
        // CRUD::field('email')->validationRules('required|email|unique:users,email');
        // CRUD::field('password')->validationRules('required');

        parent::setupCreateOperation();

        $this->crud->setValidation(StoreRequest::class);
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    public function setupUpdateOperation()
    {
        // $this->setupCreateOperation();

        // same
        // CRUD::field('name')->validationRules('required|min:5');
        // // need to pass ID to the unique rule on update to sidestep the unique constraint
        // CRUD::field('email')->validationRules('required|email|unique:users,email,'.CRUD::getCurrentEntryId());
        // // only needed if you want to change the password
        // CRUD::field('password')->hint('Type a password to change it.');

        /**
         * Laravel 10+ handles password hashing for you.
         */

        parent::setupUpdateOperation();

        $this->crud->setValidation(UpdateRequest::class);

        // CRUD::field([
        //     'name' => 'state_id',
        //     'label' => 'State',
        //     'type' => 'select',
        //     'entity' => 'state',
        //     'model' => 'App\Models\State',
        //     'attribute' => 'name',
        // ])->after('password_confirmation');

        /**
         * Add our custom JS as a script Widget.
         */
        // Widget::add()
        //     ->type('script')
        //     ->content(asset('assets/js/admin/forms/user.js'));
    }

    public function store()
    {
        // $preRequest = $this->crud->validateRequest();
        // dd($request);

        $this->cleanUpRequest();

        // $request = $this->crud->getRequest();

        // dd($preRequest->request, $request->request);

        return $this->traitStore();
    }

    public function update()
    {
        // $preRequest = $this->crud->validateRequest();
        // dd($request);

        $this->cleanUpRequest();

        // $request = $this->crud->getRequest();
        // dd($preRequest->request, $request->request);

        return $this->traitUpdate();
    }


    #[Override]
    protected function addUserFields()
    {
        // Log::debug('using ' . self::class . 'addUserFields()!');

        parent::addUserFields();

        /**
         * We could limit the states to those already having mills...
         */
        CRUD::field([
            'name' => 'state_id',
            'label' => 'State',
            'type' => 'select',
            'entity' => 'state',
            'model' => 'App\Models\State',
            'attribute' => 'name',
            'options' => (function ($query) {
                return $query->has('mills')->get();
            }),
        ])->after('password_confirmation');

        /**
         * Okay.
         * agent_role_name is a hidden field which uses the UserRoles enum to inform the string used by JavaScript 
         * to identify the State Agent checkbox label.
         * is_agent is hidden field containing a boolean string which indicates if the State Agent checkbox is checked.
         * When is_agent contains 'true', state_id becomes a required field.
         * :shrug:
         */

        CRUD::field([
            'name' => 'agent_role_name',
            'type' => 'hidden',
            'value' => UserRoles::AGENT,
        ]);

        CRUD::field([
            'name' => 'is_agent',
            'type' => 'hidden',
            'value' => 'false',
        ]);

        /**
         * Add our custom JS as a script Widget.
         */
        Widget::add()
            ->type('script')
            ->content(asset('assets/js/admin/forms/user.js'));

    }

    protected function cleanUpRequest()
    {
        /**
         * modified from parent
         */
        $this->crud->setRequest($this->crud->validateRequest());
        /**
         * here's where we do differently
         * remove agent_role_name and is_agent before passing the request to handlePasswordInput()
         */
        $request = $this->crud->getRequest();
        $request->request->remove('agent_role_name');
        $request->request->remove('is_agent');

        $this->crud->setRequest($this->handlePasswordInput($request));
        // $this->crud->setRequest($this->handlePasswordInput($this->crud->getRequest()));
        $this->crud->unsetValidation(); // validation has already been run
    }
}
