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
        if ($this->batch()->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently this job batch was cancelled?');

            return;
        }

        /**
         * what all do we need to do?
         *  - relationships
         *      - state_id should already be present
         *      - county_id
         *      - mailing_state_id
         *      - mill_types
         *      - wood_species
         */

    }
}
