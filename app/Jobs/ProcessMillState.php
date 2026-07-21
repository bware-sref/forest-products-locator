<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use App\Models\County;
use App\Models\State;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessMillState implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     *
     * $allowFailures: false (default) fails this job for real on error, which
     * aborts the rest of this mill's chain and cancels a strict batch (the
     * spreadsheet-import pipeline's intent). true logs + records the failure
     * and returns normally instead, so a batch built to tolerate failures
     * (ArcGIS imports) keeps going. See ProcessMill::jobChain().
     */
    public function __construct(
        public Mill $mill,
        public bool $allowFailures = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->mill->import_id.' or Mill #'.$this->mill->id.' was cancelled?');

            return;
        }

        try {
            $this->assignState();
        } catch (Throwable $e) {
            $msg = self::class.": failed to process Mill #{$this->mill->id}: {$e->getMessage()}";
            Log::error($msg);
            $this->mill->recordProcessingFailure($msg);

            if (! $this->allowFailures) {
                throw $e;
            }
        }
    }

    private function assignState(): void
    {

        /**
         * what all do we need to do?
         *  - relationships
         *      - address (a.k.a., state and county)
         *      - state_id should already be present, but we should double check.
         *      - county_id
         *      - mailing_state_id
         *      - mill_types
         *      - wood_species
         * 
         * We need to be sure we have a usable address at this point.
         * 
         * Note: don't check for physical_state yet because lack of physical_state should emit an error.
         * If this mill was added via import, the import record might have a state_id.
         */
        $needsStateId = empty($this->mill->state_id);
        /**
         * However, make sure we have things we need before fooling with the optional items.
         * 
         * Okay.
         * Clearly we might still the county despite not having that info here.
         */
        $needsCountyId = (empty($this->mill->county_id) && !empty($this->mill->county_name));
        $needsMailingStateId = (empty($this->mill->mailing_state_id) && !empty($this->mill->mailing_state));

        /**
         * If this Mill has all its fields full, there's nothing for us to do!
         */
        if (! $needsStateId && ! $needsCountyId && ! $needsMailingStateId) {
            Log::debug(
                self::class.": Mill #{$this->mill->id} already has state_id, county_id, and mailing_state_id.", 
                collect($this->mill->toArray())->only(['state_id', 'county_name', 'county_id', 'mailing_state_id'])->toArray()
            );
            return;
        }

        /**
         * Now we can look up the state...except we need to be sure that we have the data necessary to proceed.
         * physical_state
         * county_name
         * mailing_state
         * 
         * Let's go ahead and create the state variable so we may be able to reuse it for mailing_state_id.
         * Except...we need to pull the county lookup out of the state lookup.
         * Otherwise, we'll only get county_id when need state_id
         * 
         * Fetching state with only the one county we want is not working as expected.
         * However, instead of chasing bugs that aren't show stoppers, we're just going to search the returned counties.
         */
        /**
         * @var State
         */
        $state = $this->mill->state ?? State::byNameOrAbbreviation($this->mill->physical_state, $this->mill->county_name ?? null);

        // Log::debug(self::class.": looked up state and county for Mill #{$this->mill->id}. Found: ", [
        //     'mill_physicalState' => $this->mill->physical_state,
        //     'mill_countyName' => $this->mill->county_name ?? '?!?twas empty?!?',
        //     'state?' => $state?->toArray(),
        //     'howManyCounties?' => \count($state?->counties),
        // ]);

        /**
         * More different error if no state found.
         */
        if (! $state) {
            $msg = self::class.": unable to process Mill #{$this->mill->id} because no State found for '{$this->mill->physical_state}'.";
            Log::error($msg, $this->mill->toArray());
            throw new \RuntimeException($msg);
        }

        if ($needsStateId) {
            /**
             * This should never be the case because already used mill->physical_state to lookup the state.
             * 
             */
            if (empty($this->mill->physical_state)) {
                /**
                 * This is not good.
                 * It shouldn't actually ever be the case because the validation rules require physical_state.
                 * But we need to check anyway because the DB doesn't actually require physical_state, only
                 * id and match_id.
                 * Which is kinda bonkers because you can't create a match_id without a mill_name, and a mill without a state
                 * doesn't do us much good.
                 * In any case...
                 */
                $msg = self::class.": unable to process Mill #{$this->mill->id} because it does not have a value for 'physical_state'.";
                Log::error($msg, $this->mill->toArray());
                throw new \RuntimeException($msg);
            }

            /**
             * At present, physical_state only has state postal abbreviation values.
             * However, in future it might have full state names, so we'll look for both.
             * 
             * It's tempting to use firstOrFail() but if we can't find the state, it seems more useful to have a more specific
             * indicator of that situation.
             * Also, let's set up the basic query then check to see if we need to also look up the county
             */
            // $stateQuery = State::where('abbreviation', $this->mill->physical_state, null, 'and')
            //     ->orWhere('name', $this->mill->physical_state);
            
            // if ($needsCountyId) {
            //     $stateQuery = $stateQuery->with(['counties' => function ($query) {
            //         $query->where('name', $this->mill->county_name);
            //     }]);
            // }
                
            // $state = $stateQuery->first();

            // $state = State::byNameOrAbbreviation($this->mill->physical_state, $this->mill->county_name ?? null);

            $this->mill->state_id = $state->id;
            // Log::debug(self::class.": Mill #{$this->mill->id} belongs to the great state of {$state->name} (#{$state->id})!");
        }

        if ($needsCountyId) {
            /**
             * If no county found, it's only a warning.
             * In any case, county assignment needs to be wholely independent of needStateId
             */
            if (empty($state->counties) || 1 > \count($state->counties)) {
                Log::warning(self::class.": Mill #{$this->mill->id}, county '{$this->mill->county_name}' not found in {$state->name}.");
            } else {
                /**
                 * The query to only select one county isn't working.
                 * I.e., we're getting the full list of counties here.
                 * However, if there's only one county, use it.
                 * Otherwise, look the fucker up by name.
                 */
                $county = (1 === \count($state->counties)) ? 
                    $state->counties->first() : // [0] : 
                    $state->counties()->where('name', $this->mill->county_name)->first();

                /**
                 * Check that we have a county before using it!
                 */
                if (empty($county)) {
                    Log::warning(self::class.": Mill #{$this->mill->id}: failed to find county '{$this->mill->county_name}' in the state of {$state->name}. Skipping.");
                } else {
                    $this->mill->county_id = $county->id ?? null;
                    // $countyType = ucfirst($county->type ?? '');
                    // Log::debug(self::class.": Mill #{$this->mill->id} is located in {$county->name} {$countyType}, {$state->name} (#{$state->id})!");

                }
            }
        }

        /**
         * now handle mailing state, if needed.
         */
        if ($needsMailingStateId) {

            /**
             * if physical_state and mailing_state differ, we need to fetch another state
             */
            if ($this->mill->physical_state !== $this->mill->mailing_state) {
                // $physicalState = $state; // just in case
                $state = State::byNameOrAbbreviation($this->mill->mailing_state);

                /**
                 * Not finding mailing_state is also only a warning.
                 */
                if (! $state) {
                    Log::warning(self::class.": Mill #{$this->mill->id}, unable to find mailing_state '{$this->mill->mailing_state}'.");
                    // $state = $physicalState;
                }
            }

            /**
             * if there's still a state, use it's id
             */
            $this->mill->mailing_state_id = $state?->id ?? null;

            // Log::debug(self::class.": Mill #{$this->mill->id}'s mail is delivered to the great state of {$state->name} (#{$state->id})!");
        }

        // persist our updates
        $this->mill->save();

    }
}
