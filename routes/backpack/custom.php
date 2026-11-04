<?php

use Illuminate\Support\Facades\Route;

// --------------------------
// Custom Backpack Routes
// --------------------------
// This route file is loaded automatically by Backpack\CRUD.
// Routes you generate using Backpack\Generators will be placed here.

Route::group([
    'prefix' => config('backpack.base.route_prefix', 'admin'),
    'middleware' => array_merge(
        (array) config('backpack.base.web_middleware', 'web'),
        (array) config('backpack.base.middleware_key', 'admin')
    ),
    'namespace' => 'App\Http\Controllers\Admin',
    'as' => config('backpack.base.route_prefix', 'admin') . '.',
], function () { // custom admin routes
    /**
     * permission related routes
     */
    Route::crud('user', 'UserCrudController');
    Route::crud('permission', 'PermissionCrudController');
    Route::crud('role', 'RoleCrudController');

    Route::crud('county', 'CountyCrudController');
    Route::crud('mill', 'MillCrudController');
    // Route::crud('mill-edits', 'MillEditCrudController');
    Route::crud('mill-type', 'MillTypeCrudController');
    Route::crud('state', 'StateCrudController');
    Route::crud('wood-species', 'WoodSpeciesCrudController');
    Route::crud('faq-category', 'FaqCategoryCrudController');
    Route::crud('faq', 'FaqCrudController');
    // the same routes backpack/activity-log registers, pointed at our permission-checked subclass
    Route::crud('activity-log', 'ActivityLogCrudController');
    Route::get('activity-log/causer', 'ActivityLogCrudController@getCauserOptions');
    Route::get('activity-log/subject', 'ActivityLogCrudController@getSubjectOptions');
    Route::get('statistics', 'StatisticsController@index')
        ->name('page.statistics.index');
    Route::get('statistics/state/{state?}', 'StatisticsController@state')
        ->name('page.statistics.state');
    Route::get('statistics/submissions', 'StatisticsController@submissions')
        ->name('page.statistics.submissions');
    Route::get('statistics/activity', 'StatisticsController@activity')
        ->name('page.statistics.activity');
    Route::get('statistics/similar', 'StatisticsController@similar')
        ->name('page.statistics.similar');
    Route::get('statistics/compare', 'StatisticsController@compare')
        ->name('page.statistics.compare');
    Route::get('state-hub/{state?}', 'StateHubController@show')
        ->name('page.state-hub.show');
    Route::crud('page-seo', 'PageSeoCrudController');
    Route::crud('state-page', 'StatePageCrudController');
    Route::crud('state-contact', 'StateContactCrudController');
    Route::crud('state-forest-overview', 'StateForestOverviewCrudController');
    Route::crud('state-forest-type', 'StateForestTypeCrudController');
    Route::crud('state-forest-product', 'StateForestProductCrudController');
    Route::crud('state-economic-impact', 'StateEconomicImpactCrudController');
    Route::crud('state-forestry-agency', 'StateForestryAgencyCrudController');
    Route::crud('state-assistance-category', 'StateAssistanceCategoryCrudController');
    Route::crud('state-assistance-link', 'StateAssistanceLinkCrudController');
}); // this should be the absolute last line of this file

/**
 * DO NOT ADD ANYTHING HERE.
 */
