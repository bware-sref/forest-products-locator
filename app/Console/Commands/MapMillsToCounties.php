<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Mill;
use App\Models\State;
use Illuminate\Console\Command;

class MapMillsToCounties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:mill-counties';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and Counties';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->newLine(2);

        // count mills with county_id null
        $millCount = Mill::query()
            ->whereNull('county_id')
            ->count();

        if (1 > $millCount) {
            $this->info('No Mills without counties. Exiting.');
            return self::SUCCESS;
        }

        // get the states which have mills
        $statesWithMills = State::has('mills')
            ->get();

        $this->info(sprintf(
            '%d Mills with county_id null across %d states.',
            $millCount,
            count($statesWithMills)
        ));

        $totalAffected = 0;

        foreach ($statesWithMills as $state) {
            $stateTotal = 0;
            // get the county names from Mills so we can filter the state's counties
            $countyNames = $state->mills()
                ->select('county')
                ->whereNull('county_id')
                ->distinct()
                ->pluck('county')
                ->toArray();

            $millsInState = count($state->mills);

            $this->info(sprintf(
                '%s has %d mills in %d counties.',
                $state->name,
                $millsInState,
                count($countyNames)
            ));

            $counties = $state->counties()
                ->whereIn('name', $countyNames)
                ->get()
                ->keyBy('name');

            foreach ($counties as $countyName => $county) {
                $countyAffected = $state->mills()
                    ->whereNull('county_id')
                    ->where('county', $countyName)
                    ->update(['county_id' => $county->id]);
                $this->info(sprintf(
                    'Updated %d Mills in %s, %s.',
                    $countyAffected,
                    $county->full_name,
                    $state->name
                ));

                $stateTotal += $countyAffected;
            }

            $this->info(sprintf(
                'Update %d Mills total in %s.',
                $stateTotal,
                $state->name
            ));

            $totalAffected += $stateTotal;
        }

        $this->info(sprintf(
            'Updated %d Mills across %d states.',
            $totalAffected,
            count($statesWithMills)
        ));

        // get states of mills with county_id null
        // $millStates = Mill::query()
        //     ->whereNull('county_id')
        //     ->limit(2)
        //     ->get();
        

        // $aState = $millStates[0]->state;

        // dd($aState->counties->toArray());

        // dd($aState);
        // dd($millStates);
    }
}
