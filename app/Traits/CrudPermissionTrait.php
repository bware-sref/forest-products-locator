<?php

namespace App\Traits;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait CrudPermissionTrait
{
    // all CRUD operations
    // corresponds to 'edit' in permission levels
    public const array EDIT_OPERATIONS = [
        'list',
        'show',
        'create',
        'update',
        'delete',
    ];

    // corresponds to 'see' in permission levels
    public const array SEE_OPERATIONS = [
        'list',
        'show',
    ];

    /**
     * I think we need more permission levels, or at least operation groups.
     * E.g., we need something for approving mill edits and additions as well as for importing mill spreadsheets.
     * However, since that really only impacts mills, it should perhaps be confined to and implemented by the MillCrudController.
     * Also, we have things like the StatisticsController which have no corresponding model, making them unsuitable for this approach.
     */

    /**
     * Set CRUD access using Spatie Permissions defined for the logged-in user.
     * 
     * @return void
     */
    public function setAccessUsingPermissions(): void
    {
        // deny all by default
        CRUD::denyAccess(self::EDIT_OPERATIONS);

        // get context
        $table = CRUD::getModel()->getTable();
        $user = request()->user();

        // bail without allowing anything if no user
        if (!$user) {
            return;
        }

        // enable CRUD operations depending on permission level
        foreach ([
            // permission level => [crud ops]
            'see' => self::SEE_OPERATIONS,
            'edit' => self::EDIT_OPERATIONS,
        ] as $level => $ops) {
            /**
             * e.g., if user has users.see permissions, grant access
             */
            if ($user->can("$table.$level")) {
                CRUD::allowAccess($ops);
            }
        }
    }
}
