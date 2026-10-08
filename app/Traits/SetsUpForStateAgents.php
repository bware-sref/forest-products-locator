<?php

namespace App\Traits;

// use App\Models\State;
use App\Models\StatePage;
use App\Models\StateEconomicImpact;
use App\Models\StateForestOverview;
use App\Models\StateForestryAgency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\CRUD\app\Library\Widget;

trait SetsUpForStateAgents
{

    public const array canBeOnlyOne = [
        StatePage::class,
        StateEconomicImpact::class,
        StateForestOverview::class,
        StateForestryAgency::class,
    ];

    public function doSetupForStateAgent(): void
    {
        if (! backpack_user()->isStateAgent()) {
            // we could log but seems silly
            return;
        }

        // we could either list the Models that are highlanders or pass a parameter.
        Log::debug("\n".self::class."::doSetupForAgent(): model? ", [
            "\nmodel\n" => $this->crud->model,
            "\ngetModel()\n" => $this->crud->getModel(),
        ]);

        /**
         * If model can be only one, do limitOnePerState()
         * Otherwise, restrict to home state.
         * So do we list the Highlanders in a class constant or pass that as a parameter?
         */
        if (\in_array(CRUD::getModel(), self::canBeOnlyOne)) {
            self::limitOnePerState();
            return;
        }

        self::restrictToHomeState();
    }

    protected function restrictToHomeState(): void
    {
        CRUD::operation(['show', 'update', 'delete'], function () {
            CRUD::setAccessCondition(['show', 'update', 'delete'], function ($entry) {                
                return $entry->state_id === backpack_user()->state_id;
            });
        });
    }

    protected function limitOnePerState(): void
    {
        /**
         * disallow create and delete altogether
         */
        CRUD::denyAccess(['create', 'delete']);
        CRUD::operation(['list', 'show', 'update', 'delete'], function () {
            CRUD::setAccessCondition(['show', 'update', 'delete'], function ($entry) {
                return $entry->state_id === backpack_user()->state_id;
            });
        });

    }
}
