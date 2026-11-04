<?php

namespace App\Http\Controllers\Admin;

use App\Traits\CrudPermissionTrait;
use Backpack\ActivityLog\Http\Controllers\ActivityLogCrudController as BaseActivityLogCrudController;
use Illuminate\Http\Request;

/**
 * The backpack/activity-log screen, without its Pro-only filters, limited to users with activity_log.see (Superadmins, to start with).
 * The add-on checks no permissions itself, and its routes are registered again in routes/backpack/custom.php so that
 * they reach this controller instead of the add-on's.
 */
class ActivityLogCrudController extends BaseActivityLogCrudController
{
    use CrudPermissionTrait;

    public function setup()
    {
        parent::setup();
        $this->setAccessUsingPermissions(); // checks activity_log.see
    }

    /**
     * The add-on's list filters (by user, by record) need Backpack Pro, which we don't have; without this override
     * the list page fails to render. The list can still be searched and sorted.
     */
    public function setupFilters()
    {
    }

    /**
     * The list's causer and subject filters load their options from these, outside any CRUD operation.
     */
    public function getCauserOptions(Request $request): array
    {
        $this->crud->hasAccessOrFail('list');

        return parent::getCauserOptions($request);
    }

    public function getSubjectOptions(Request $request): array
    {
        $this->crud->hasAccessOrFail('list');

        return parent::getSubjectOptions($request);
    }
}
