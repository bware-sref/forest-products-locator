<?php
// routes/backpack/permissionmanager.php
/*
|--------------------------------------------------------------------------
| Backpack\PermissionManager Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are
| handled by the Backpack\PermissionManager package.
|
*/

/**
 * @HEY: these mofos got moved to routes/backpack/custom.php.
 * However, the existence of this file prevents Backpack from 
 * registering its default version of this file, which would 
 * override the routes we moved to custom.php.
 * Routes are sorta like CSS rules:  the last rule has precedence.
 */

// Route::group([
//     // 'namespace'  => 'Backpack\PermissionManager\app\Http\Controllers',
//     'namespace'  => 'App\Http\Controllers\Admin',
//     'prefix'     => config('backpack.base.route_prefix', 'admin'),
//     'middleware' => ['web', backpack_middleware()],
// ], function () {
//     Route::crud('permission', 'PermissionCrudController');
//     Route::crud('role', 'RoleCrudController');
//     // Route::crud('user', 'UserCrudController');
// });
