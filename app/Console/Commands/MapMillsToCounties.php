<?php

namespace App\Console\Commands;

use App\Models\County;
use App\Models\Mill;
use App\Models\State;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
            /**
             * Phucking intelliphense
             */
            ->whereNull('county_id', boolean: 'and', not: false)
            ->count(columns: '*');

        if (1 > $millCount) {
            $this->info('No Mills without counties. Exiting.');
            return self::SUCCESS;
        }

        // get the states which have mills
        $statesWithMills = State::has('mills')
            ->get();

        $this->info(\sprintf(
            '%d Mills with county_id null across %d states.',
            $millCount,
            \count($statesWithMills)
        ));

        $totalAffected = 0;

        $audit = [];

        foreach ($statesWithMills as $state) {
            // fancy "modern" (PHP >=7.4) syntax
            $audit[$state->name] ??= [
                'total' => 0,
                'updated' => 0,
            ];            

            $stateAffected = 0;
            // get the county names from Mills so we can filter the state's counties
            $millCountyNames = $state->mills()
                ->select('county_name')
                ->whereNull('county_id')
                ->distinct()
                ->pluck('county_name')
                ->transform(
                    fn($countyName) => Str::of($countyName)
                        ->lower()
                        ->trim()
                        ->replaceEnd(' county', '')
                        ->replaceEnd(' city', '')
                        ->ucwords()
                        ->toString()
                )
                ->unique()
                ->sort();
                // ->toArray();
            // dd($millCountyNames);
            // let's check to see where we go awry
            

            // this is all mills in the state, regardless of their county status
            $totalMillsInState = count($state->mills);

            $millsInStateNoCounty = $state->mills()
                ->whereNull('county_id')
                ->count();

            $audit[$state->name]['total'] = $totalMillsInState;
            $audit[$state->name]['millsWithoutCounty'] = $millsInStateNoCounty;
            // for kicks
            // and also to blow up output without adding utility!
            // maybe do it for states that have significant mismatches
            // $audit[$state->name]['mills.county_names'] = $millCountyNames->toArray();
            $millCountyNamesCount = count($millCountyNames);
            $audit[$state->name]['mills.countyNamesCount'] = $millCountyNamesCount; 

            $this->info(sprintf(
                '%s has %d mills with no county_id across %d counties.',
                $state->name,
                // $totalMillsInState,
                $millsInStateNoCounty,
                $millCountyNamesCount
            ));

            // this might be where we went wrong
            // if the number of counties here doesn't match count
            $counties = $state->counties()
                ->whereIn('name', $millCountyNames)
                ->orderBy('name')
                ->get()
                ->keyBy('name');
            // these keep being empty

            /**
             * if there's a mismatch between the number of County names extracted from Mills and the County names that
             * were selectable via the State relationship...
             * this all so convoluted and in the name of efficiency. B0)
             * peeling through the list of Mills would at least allow identifying the specific problem data.
             */
            $stateCountiesCount = count($counties);
            if ($millCountyNamesCount != $stateCountiesCount) {

                $this->warn(sprintf(
                    '%d mills.county_name values for %s, but only %d from state match.',
                    $millCountyNamesCount,
                    $state->name,
                    $stateCountiesCount
                ));

                // $this->warn(sprintf(
                //     'mill.county_names: %s',
                //     print_r($millCountyNames->toArray(), true)
                // ));

                if (0 < $stateCountiesCount) {
                    $stateCountyNames = array_keys($counties->toArray());
                    // $this->warn(sprintf(
                    //     '%s stateCountyNames filtered by mill.county_name: %s',
                    //     $state->name,
                    //     print_r($stateCountyNames, true)
                    // ));

                    $millCountyDiff = array_diff($millCountyNames->toArray(), $stateCountyNames);
                    $this->warn(sprintf(
                        '%s Counties in mill.county_name that are not in state.county.name: %s',
                        $state->name,
                        print_r($millCountyDiff, true)
                    ));
                    $stateCountyDiff = array_diff($stateCountyNames, $millCountyNames->toArray());
                    $this->warn(sprintf(
                        '%s state.county.name not in mill.county_name: %s',
                        $state->name,
                        print_r($stateCountyDiff, true)
                    ));
                }

                // $allStateCountyNames = $state->counties()
                //     ->orderBy('name')
                //     ->get()
                //     ->keyBy('name')
                //     ->toArray();

                // $allStateCountyNamesONLY = array_keys($allStateCountyNames);

                // $this->warn(sprintf(
                //     'all counties in %s: %s',
                //     $state->name,
                //     print_r($allStateCountyNamesONLY, true)
                // ));


                // $diffAllStateAndMills = array_diff($millCountyNames->toArray(), $allStateCountyNamesONLY);
                // $this->warn(sprintf(
                //     'diff of %s mill counties and all state counties: %s',
                //     $state->name,
                //     print_r($diffAllStateAndMills, true)
                // ));

                /**
                 * relationships don't have the withoutAppends method so we need to select via Mills
                 * oookay, turns out that withoutAppends() comes from Eloquent API resources
                 */
                $millsWithoutCounty = $state->mills()
                    ->select('mill_name', 'county_name', 'physical_state')
                    ->where('state_id', $state->id)
                    ->whereNull('county_id')
                    ->get()                  
                    ->toArray();
                
                // only add the number of orphans to the audit
                $audit[$state->name]['orphanMillCount'] = count($millsWithoutCounty);

                // send the full list to the log
                Log::warning(sprintf(
                    '%d Orphan mills in %s: %s',
                    $audit[$state->name]['orphanMillCount'],
                    $state->name,
                    print_r($millsWithoutCounty, true)
                ));

                /**
                 * we could always remove ' County' from the end of CountyName values and repopulate
                 */
                // dd($audit);
            }

            foreach ($counties as $countyName => $county) {
                // mill.county_name sometimes ends in ' County'
                // if we clean it up here, we might get more matches
                $countyAffected = $state->mills()
                    ->whereNull('county_id')
                    ->where('county_name', $countyName)
                    ->update(['county_id' => $county->id]);

                $this->info(sprintf(
                    'Updated %d Mills in %s, %s.',
                    $countyAffected,
                    $county->full_name,
                    $state->name
                ));

                $stateAffected += $countyAffected;
            }

            $this->info(sprintf(
                'Updated %d Mills total in %s.',
                $stateAffected,
                $state->name
            ));

            $audit[$state->name]['updated'] = $stateAffected;

            $totalAffected += $stateAffected;
        }

        $this->info(sprintf(
            'Updated %d Mills across %d states.',
            $totalAffected,
            count($statesWithMills)
        ));

        dump($audit);

        // $aState = $millStates[0]->state;

        // dd($aState->counties->toArray());

        // dd($aState);
        // dd($millStates);
    }
}
