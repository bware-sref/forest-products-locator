<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\FaqRequest;
use App\Models\FaqCategory;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;
use Closure;
use Illuminate\Database\Eloquent\Builder;


/**
 * Class FaqCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class FaqCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    use CrudPermissionTrait;

    public const string FILTER_KEY = 'faq_category_id';

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Faq::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/faq');
        CRUD::setEntityNameStrings('FAQ', 'FAQs');

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
            $this->crud
                ->orderBy('faq_category_id', 'asc')
                ->orderBy('order', 'asc');
        }

        $this->doFilter();

        CRUD::column('question')
            ->type('text')
            ->orderable(true);

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        CRUD::column('faq_category_id')
            ->type('select')
            ->entity('faqCategory')
            ->model('App\Models\FaqCategory')
            ->attribute('name')
            ->orderable(true);

        CRUD::column('order')
            ->type('number')
            ->label('Sort Weight')
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
        CRUD::setValidation(FaqRequest::class);
        CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
        
        CRUD::field('slug')->remove();
            // ->type('text')
            // ->label('Slug')
            // ->attributes([
            //     'placeholder' => 'Leave blank to create Slug from Question'
            // ]);

        CRUD::field('faq_category_id')
            ->label('Category')
            ->type('select')
            ->entity('faqCategory')
            ->attributes([
                'model' => FaqCategory::class,
                'attribute' => 'name',
            ]);

        CRUD::field('order')
            ->label('Sort Weight')
            ->type('number');
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
     * @return void
     */
    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    protected function doFilter(?callable $fn = null): void
    {
        $this->addFilterWidget();

        if (! $this->shouldApplyFilter()) {
            return;
        }

        /**
         * If we received a Closure, execute it and return.
         */
        if ($fn instanceof Closure) {
            $this->crud->addClause($fn);
            return;
        }

        /**
         * If no Closure, use the default filter query
         */
        $this->crud->addClause($this->applyFilterQuery(...));
    }

    public function addFilterWidget(): void
    {
        /**
         * Insert custom filter widget
         * Do we want to extract any options?
         * Not right now.
         */
        Widget::add([
            'type' => 'charcoal',
            'wrapper' => ['class' => 'col-12-sm'],
            'options' => $this->getFilterOptions(),
            'filterKey' => self::FILTER_KEY,
            'filterLabel' => 'FAQ Category',
        ])->to('before_content');

    }

    protected function shouldApplyFilter(): bool
    {
        return ! empty($this->getFilterValue());
    }

    protected function getFilterValue(): ?int
    {
        return $this->crud->getRequest()->input(self::FILTER_KEY, null);
    }

    protected function getFilterOptions(): array
    {
        return FaqCategory::whereHas('faqs')
            ->get()
            // ->pluck('name', 'id')
            ->toArray();
    }

    protected function applyFilterQuery(Builder $builder): Builder
    {
        return $this->crud->addClause('where', self::FILTER_KEY, $this->getFilterValue());
    }
}
