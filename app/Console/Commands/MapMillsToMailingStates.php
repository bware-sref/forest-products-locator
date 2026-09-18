<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Mill;
// use App\Models\MillType;
use App\Models\State;
// use App\Models\WoodSpecies;
use Illuminate\Console\Command;

class MapMillsToMailingStates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:map-mill-mailing-states {--dry-run : explore the situation without updating the database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and Mailing States';

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
        // get count of Mills with mailing_state but without mailing_state_id
        $millCount = Mill::query()
            ->whereNotNull('mailing_state')
            ->whereNot('mailing_state', '')
            ->whereNull('mailing_state_id', boolean: 'and', not: false)
            ->count('*');

        $this->newLine(2);

        // if none exist, exit
        if (empty($millCount) || 1 > $millCount) {
            $this->info('No Mills found without a Mailing State relationship. Exiting.');
            return self::SUCCESS;
        }
        
        $this->info(\sprintf('Found %d Mills with mailing_state but without a mailing_state_id (relationship).', $millCount));

        // get distinct mailing_state where mailing_state_id null
        $millStates = Mill::query()
            ->select('mailing_state')
            ->whereNotNull('mailing_state')
            ->whereNot('mailing_state', '')
            ->whereNull('mailing_state_id', boolean: 'and', not: false)
            ->distinct()
            ->pluck('mailing_state')
            ->toArray();

        if (empty($millStates)) {
            $this->error(\sprintf('Weird. No mills.mailing_state values found despite %d mills without a mailing_state_id.', $millCount));
            return self::FAILURE;
        }
        

        // fetch states with at least one Mill
        $states = State::query()
            ->whereIn('abbreviation', $millStates, boolean: 'and', not: false)
            ->get();

        if ($this->option('dry-run')) {
            $this->newLine(2);
            $this->info('millStates:');
            dump($millStates);

            // $this->newLine();

            /**
             * This only gets the count for the first state.
             */
            // $countByState = Mill::query()
            //     ->select(['mailing_state'])
            //     ->whereNotNull('mailing_state')
            //     ->whereNot('mailing_state', '')
            //     ->groupBy('mailing_state')
            //     ->count('*');
            // dump($countByState);
            // $this->info('States having Mills lacking mailing_addresses: ');
            // dump($states->toArray());
            return self::SUCCESS;
        }

        // accounting
        $totalAffected = 0;

        foreach ($states as $state) {
            $affectedRows = Mill::query()
                ->where('mailing_state', $state->abbreviation)
                ->whereNull('mailing_state_id', boolean: 'and', not: false)
                ->update(['mailing_state_id' => $state->id]);
            
            // $affectedRows += Mill::query()
            //     ->where('mailing_state', $state->abbreviation)
            //     ->whereNull('mailing_state_id')
            //     ->update(['mailing_state_id' => $state->id]);

            if (empty($affectedRows) || 1 > $affectedRows) {
                $spoztaBeen = Mill::query()
                    ->where('mailing_state', $state->abbreviation)
                    ->whereNull('mailing_state_id', boolean: 'and', not: false)
                    ->count('*');
                $this->warn(\sprintf(
                    'No Mills updated for state abbreviation "%s", yet %d have that mailing_state and no mailing_state_id!?!',
                    $state->abbreviation,
                    $spoztaBeen
                ));
                continue;
            }

            $totalAffected += $affectedRows;

            $this->info(\sprintf(
                '%d Mills mailing_state_id updated to belong to %s.',
                $affectedRows,
                $state->name
            ));
        }

        $this->info(\sprintf(
            'Updated Mailing State relationships for %d Mills.',
            $totalAffected
        ));

        return self::SUCCESS;
    }
}
