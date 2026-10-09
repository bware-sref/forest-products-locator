<?php

namespace App\Http\Controllers\Admin;

use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\PermissionManager\app\Http\Controllers\PermissionCrudController as BasePermissionCrudController;
use Illuminate\Http\Request;

class PermissionCrudController extends BasePermissionCrudController
{
    //
    // use CreateOperation;
    // use CrudPermissionTrait;
    // use DeleteOperation;
    // use ListOperation;
    // use ShowOperation;
    // use UpdateOperation;
    use CrudPermissionTrait;

    public function setup()
    {
        parent::setup();
        $this->setAccessUsingPermissions(); // checks roles.see / roles.edit
    }

}
