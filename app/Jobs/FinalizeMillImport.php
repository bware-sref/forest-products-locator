<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class FinalizeMillImport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Import $import
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        /**
         * What do we need to do?
         *  - Start a transaction
         *  - delete mills where
         *      - mill.state_id = imports.state_id
         *      - AND
         *      - mill.import_id IS NOT NULL
         *      - AND
         *      - mill.import_id !== import.id
         *  - update mills to set status to approved where mills.import_id === import.id
         */
    }
}
