<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalizeMillImport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId,
        // stateId might be null
        public ?int $stateId = null,
        // deleteFromState shouldn't ever be null, but just in case
        public ?bool $deleteFromState = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stateId = $this->stateId ?? 0; // 0 means stateId was null, since we can't use null in a string.
        Log::debug(self::class.": preparing to finalize Import #{$this->importId} for State #{$stateId}.");
        /**
         * What do we need to do?
         *  - Start a transaction
         * if stateId is not null, we need to delete other mills from this state
         *  - delete mills where
         *      - mill.state_id = imports.state_id
         *      - AND
         *      - mill.import_id IS NOT NULL
         *      - AND
         *      - mill.import_id !== import.id
         *  - update mills to set status to approved where mills.import_id === import.id
         */
        DB::transaction(function () {
            if (null !== $this->stateId && $this->deleteFromState) {
                $howManyDeletes = Mill::deleteOldImports($this->importId, $this->stateId);

                Log::debug(self::class.": deleted {$howManyDeletes} Mills from State #{$this->stateId} for Import #{$this->importId}.");
            }
            /**
             * Now publish all of the Mills from this import
             */
            $didItWork = Mill::publishFromImport($this->importId);

            Log::debug(self::class.": did publishing Mills from Import #{$this->importId} work?", [
                'didItWork' => (string) $didItWork,
            ]);
        });
        Log::debug(self::class.": finalized Import #{$this->importId} for State #{$stateId}.");
    }
}
