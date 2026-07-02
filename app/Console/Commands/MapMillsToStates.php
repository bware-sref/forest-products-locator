<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Mill;
// use App\Models\MillType;
use App\Models\State;
// use App\Models\WoodSpecies;
use Illuminate\Console\Command;

class MapMillsToStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:map-mill-states';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and States';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * I'm going about this somewhat backwards.
         * The simplest way to do this is to fetch the states and loop over them
         * 
         * then update Mills where state_id null and physical_state = state.abbreviation
         * To limit it further, we can first select the distinct physical_state
         */
        // get count of Mills with state_id null
        $millCount = Mill::query()
            ->whereNull('state_id')
            ->count();

        // if none exist, exit
        if (empty($millCount) || 1 > $millCount) {
            $this->info('No Mills found without a State relationship. Exiting.');
            return self::SUCCESS;
        }

        $this->info(\sprintf('Found %d Mills without a State relationship.', $millCount));

        // get distinct physical_state where state_id null
        $millStates = Mill::query()
            ->select('physical_state')
            ->whereNull('state_id')
            ->distinct()
            ->pluck('physical_state')
            ->toArray();

        if (empty($millStates)) {
            $this->error(\sprintf('Weird. No mill.physical_state values found despite %d mills without a state_id.', $millCount));
            return self::FAILURE;
        }
        

        // fetch states with at least one Mill
        $states = State::query()
            ->whereIn('abbreviation', $millStates)
            ->get();

        // accounting
        $totalAffected = 0;

        foreach ($states as $state) {
            $affectedRows = Mill::query()
                ->where('physical_state', $state->abbreviation)
                ->whereNull('state_id')
                ->update(['state_id' => $state->id]);
            
            if (empty($affectedRows) || 1 > $affectedRows) {
                $spoztaBeen = Mill::query()
                    ->where('physical_state', $state->abbreviation)
                    ->whereNull('state_id')
                    ->count();
                $this->warn(\sprintf(
                    'No Mills updated for state abbreviation "%s", yet %d have that physical_state and no state_id!?!',
                    $state->abbreviation,
                    $spoztaBeen
                ));
                continue;
            }

            $totalAffected += $affectedRows;

            $this->info(\sprintf(
                '%d Mills updated to belong to %s',
                $affectedRows,
                $state->name
            ));
        }

        $this->info(\sprintf(
            'Updated state relationships for %d Mills.',
            $totalAffected
        ));

        return self::SUCCESS;
    }
}
