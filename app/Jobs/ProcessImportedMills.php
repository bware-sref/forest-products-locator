<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Throwable;

class ProcessImportedMills implements ShouldQueue
{
    use Batchable, Queueable;

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
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.": Apparently the job batch for Import #{$this->import->id} was cancelled?");

            return;
        }

        /**
         * Store import->id and import->state_id so we can `use` them in the `then` callback
         */
        $importId = $this->import->id;
        $stateId = $this->import->state_id;
        $deleteFromState = $this->import->delete_from_state;

        Log::debug(self::class.": setting up job batch to process {$this->import->imported_rows} mills for Import #{$this->import->id}.");

        /**
         * we should make sure we get mills back before tearing out
         * NOTE: the Mill::pending() scope disables the globally enabled ApprovedScope.
         */
        $mills = Mill::query()
            ->pending()
            ->where('import_id', $this->import->id)
            ->lazyById();

        if (1 > $mills->count()) {
            Log::error(self::class." no mills found for import #{$this->import->id}?!?");
            return;
        }

        $jobs = [];
        
        foreach ($mills as $mill) {
            /**
             * We should just dispatch a single ProcessMill job for each mill.
             * Otherwise we'll have to keep these both in sync.
             */
            $jobs[] = new ProcessMill($mill);
        }

        $batch = Bus::batch($jobs)
            ->before(function (Batch $batch) use ($importId) {
                /**
                 * We can't use $this in these callbacks because the methods are serialized and then executed in a different context.
                 */
                Log::debug(self::class.": Before Batch #{$batch->id} processing Import #{$importId}.");
            })->progress(function (Batch $batch) use ($importId) {
                Log::debug(self::class.": a single job for Batch #{$batch->id} and Import #{$importId} has completed.");
            })->then(function(Batch $batch) use ($importId, $stateId, $deleteFromState) {
                /**
                 * We can't use $this in these callbacks.
                 */
                Log::debug(self::class." finished all jobs in Batch #{$batch->id} for Import #{$importId}.");
                /**
                 * Here's where we dispatch the FinalizeMillImport job.
                 */
                FinalizeMillImport::dispatch($importId, $stateId, $deleteFromState);

            })->catch(function (Batch $batch, Throwable $e) use ($importId) {
                Log::error(self::class.": exception during Batch #{$batch->id} for Import #{$importId}: ", ['msg' => $e->getMessage()]);
            })->finally(function (Batch $batch) use ($importId) {
                Log::debug(self::class.": Finally for batch #{$batch->id} and Import #{$importId}.");
            })->dispatch();
    }
}
