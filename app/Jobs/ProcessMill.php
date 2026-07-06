<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMill implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mill $mill
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

        /**
         * what all do we need to do?
         *  - relationships
         *      - address (a.k.a., state and county)
         *      - state_id should already be present
         *      - county_id
         *      - mailing_state_id
         *      - mill_types
         *      - wood_species
         * 
         * We need to be sure we have a usable address at this point.
         */

        /**
         * Do we really even need this job since we now have individual jobs for MillTypes and WoodSpecies?
         * Yes, we probably still need this job (or equivalent) because we haven't yet created the relationships for mailing_state_id and county_id.
         */

    }
}
