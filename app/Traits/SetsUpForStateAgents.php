<?php

namespace App\Traits;

use App\Models\MillType;
use App\Models\StatePage;
use App\Models\StateEconomicImpact;
use App\Models\StateForestOverview;
use App\Models\StateForestryAgency;
use App\Models\WoodSpecies;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\CrudPanel\Hooks\Facades\LifecycleHook;
use Illuminate\Database\Eloquent\Builder;

/**
 * Restricts StateAgent users to records belonging to their home state.
 *
 * Call doSetupForStateAgent() in setup(), AFTER setAccessUsingPermissions() (which starts with denyAllAccess()).
 *
 * Assumes the model has a state_id column. Controllers for models related to a State some other way
 * (e.g., StateAssistanceLink via its category) override the three *AgentState* methods below.
 */
trait SetsUpForStateAgents
{
    /**
     * Models which have exactly one record per state.
     * StateAgents may update their state's record, but not create or delete one.
     */
    public const array canBeOnlyOne = [
        StatePage::class,
        StateEconomicImpact::class,
        StateForestOverview::class,
        StateForestryAgency::class,
    ];

    public const array cantUpdateOrDelete = [
        MillType::class,
        WoodSpecies::class,
    ];

    public function doSetupForStateAgent(): void
    {
        $user = backpack_user();
        if (! $user?->isStateAgent()) {
            return;
        }

        /**
         * Shared models with no state: an agent's update or delete would affect every state, so deny those outright
         * (also hides the Edit/Delete buttons). Return early because the state scoping below assumes a state_id column.
         */
        if (\in_array(\get_class(CRUD::getModel()), self::cantUpdateOrDelete, true)) {
            CRUD::denyAccess(['update', 'delete']);
            return;
        }

        $stateId = (int) $user->state_id;

        /**
         * Scope every operation's query to the agent's state.
         * This is the actual enforcement: show/edit (getEntry()), update and delete all look records up via
         * findOrFail() on this query, so any other state's id 404s, no matter where it comes from (route,
         * request body, etc.).
         * addBaseClause() (vs. addClause()) also constrains the list view's total count.
         */
        CRUD::addBaseClause(fn (Builder $query) => $this->scopeToAgentState($query, $stateId));

        if (\in_array(\get_class(CRUD::getModel()), self::canBeOnlyOne, true)) {
            /**
             * Also hides the list view's Add and Delete buttons and the "Save and new item" save action.
             */
            CRUD::denyAccess(['create', 'delete']);
        }

        /**
         * Hook into both create (create form + store) and update (edit form + update).
         * after_setup runs after setupCreateOperation()/setupUpdateOperation(), so the fields exist by then.
         */
        foreach (['create', 'update'] as $operation) {
            LifecycleHook::hookInto($operation.':after_setup', function () use ($stateId) {
                $this->limitAgentStateField($stateId);

                // only submissions need coercing; GET just renders the form
                if (! CRUD::getRequest()->isMethod('GET')) {
                    $this->enforceAgentStateOnInput($stateId);
                }
            });
        }
    }

    /**
     * Constrain a query to records in the given state.
     */
    protected function scopeToAgentState(Builder $query, int $stateId): void
    {
        $query->where($query->getModel()->qualifyColumn('state_id'), $stateId);
    }

    /**
     * Limit the form's state select to the agent's state and preselect it.
     */
    protected function limitAgentStateField(int $stateId): void
    {
        CRUD::field('state_id')
            ->options(fn ($query) => $query->whereKey($stateId)->get())
            ->default($stateId);
    }

    /**
     * Overwrite any submitted state_id with the agent's state.
     * The FormRequest and Backpack's stripped save request are both built from this request, so validation and
     * the save both see the coerced value, whatever the browser sent.
     */
    protected function enforceAgentStateOnInput(int $stateId): void
    {
        CRUD::getRequest()->merge(['state_id' => $stateId]);
    }
}
