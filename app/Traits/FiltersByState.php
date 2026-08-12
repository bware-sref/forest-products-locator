<?php

namespace App\Traits;

use App\Models\State;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

trait FiltersByState
{
    /**
     * prefixed with STATE_ to avoid non-existent method collisions
     */
    public const string STATE_FILTER_KEY = 'state_id';
    
    /**
     * Adds a State filter to Backpack CRUD list pages.
     * Hmm...we may want to add a check to be sure we're on a list page.
     * We could add that check to addStateFilterWidget().
     */
    public function doFilterByState(?callable $fn = null): void
    {
        // add widget first so we can do if without else
        $this->addStateFilterWidget();

        /**
         * Put the logic for whether to apply the filter in a method
         * What would E. Percifeld do?
         * He'd invert this condition to allow returning when no filter is needed.
         * That also reduces nesting.
         */
        if (! $this->shouldApplyStateFilter()) {
            return;
        }

        /**
         * If we received a Closure, apply it and return.
         */
        if ($fn instanceof \Closure) {
            $this->crud->addClause($fn);
            return;
        }

        /**
         * If no Closure passed, apply the default state filter query (or overridden version in composing class).
         * Instead of passing a Closure, classes composing with this trait can override applyStateFilterQuery()
         */
        $this->crud->addClause($this->applyStateFilterQuery(...));
    }

    /**
     * Adds a State filter widget into the CRUD view.
     * We may extract options, but I'm not sure why we'd need them at present.
     * Renamed obnoxiously to avoid method name collisions.
     *
     * @return void
     */
    protected function addStateFilterWidget(): void
    {
        /**
         * Insert custom filter widget
         * Do we want to extract any options?
         * Not right now.
         */
        Widget::add([
            'type' => 'charcoal',
            'wrapper' => ['class' => 'col-12-sm'],
            'options' => static::getStateOptions(),
            'filterKey' => self::STATE_FILTER_KEY,
            'filterLabel' => 'State',
        ])->to('before_content');
    }

    /**
     * Determine if we need to add a subquery to affect filtering by State.
     *
     * @return bool
     */
    public function shouldApplyStateFilter(): bool
    {
        return ! empty($this->getStateFilterValue());
    }

    /**
     * Returns the current value of the State filter or null.
     */
    public function getStateFilterValue(): ?int
    {
        return $this->crud->getRequest()->input(self::STATE_FILTER_KEY, null);
    }

    /**
     * Returns an array of States which have Mills.
     *
     * @return array
     */
    protected static function getStateOptions(): array
    {
        return State::getMillStates(withCounties: false)->toArray();
    }

    /**
     * Adds a Builder query with a simple, generic filter query for state_id.
     * Intended for use in CrudControllers for Models with a direct relation to state_id and thus which do not need a
     * complex filter query.
     * Composing class which need a more complex query for filtering by state should override this method.
     *
     * @param Builder $query
     * @return Builder
     */
    public function applyStateFilterQuery(Builder $query): Builder
    {
        return $this->crud->addClause('where', self::STATE_FILTER_KEY, $this->getStateFilterValue());
    }
}
