<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\PageSeoRequest;
use App\Models\PageSeo;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
use Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PageSeoCrudController
 *
 * @property-read CrudPanel $crud
 */
class PageSeoCrudController extends CrudController
{
    use CreateOperation;
    use CrudPermissionTrait;
    use DeleteOperation;
    use ListOperation;
    use ShowOperation;
    use UpdateOperation;

    /**
     * Static pages that support an SEO override. Keep in sync with the
     * `seo_for`/`PageSeo::resolve()` call sites in routes/web.php and the
     * relevant controllers -- an entry here with no matching call site (or
     * vice versa) is silently inert.
     */
    public const array MANAGED_ROUTES = [
        'home' => 'Home',
        'about-us' => 'About Us',
        'accessibility' => 'Accessibility',
        'sitemap' => 'Sitemap',
        'faqs' => 'FAQs',
        'contact' => 'Contact',
        'add-business' => 'Add a Business',
        'mill-list' => 'Mill List',
        'mill-map' => 'Mill Map',
        'state-resources' => 'State Resources',
    ];

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(PageSeo::class);
        CRUD::setRoute(config('backpack.base.route_prefix').'/page-seo');
        CRUD::setEntityNameStrings('page SEO override', 'page SEO overrides');

        $this->setAccessUsingPermissions();
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     *
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('route_name')
            ->type('text')
            ->label('Page')
            ->orderable(true);

        CRUD::column('title')
            ->type('text')
            ->orderable(true);

        CRUD::column('description')
            ->type('text')
            ->limit(80);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     *
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PageSeoRequest::class);

        CRUD::field([
            'name' => 'route_name',
            'label' => 'Page',
            'type' => 'select_from_array',
            'options' => self::MANAGED_ROUTES,
            'allows_null' => false,
            'hint' => 'Which page this override applies to.',
        ]);

        CRUD::field([
            'name' => 'title',
            'label' => 'Title override',
            'type' => 'text',
            'hint' => "Leave blank to use the page's own default title.",
        ]);

        CRUD::field([
            'name' => 'description',
            'label' => 'Description override',
            'type' => 'textarea',
            'hint' => "Leave blank to use the page's own default description, or the site-wide default if it has none.",
        ]);

        CRUD::field([
            'name' => 'og_image',
            'label' => 'Social share image URL',
            'type' => 'text',
            'hint' => 'Absolute or app-relative URL. Leave blank to use the site logo.',
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     *
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }
}
