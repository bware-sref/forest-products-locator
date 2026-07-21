<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
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
     *
     * $allowFailures should stay false for user-uploaded spreadsheets — a bad
     * row cancelling the batch is a signal to fix and re-upload. ArcGIS-sourced
     * imports (see ProcessArcGisImport) pass true, since API data always has
     * some unusable rows and one bad feature shouldn't block the rest.
     */
    public function __construct(
        public Import $import,
        public bool $allowFailures = false,
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

        $jobChains = [];

        foreach ($mills as $mill) {
            /**
             * Pass chains directly into the batch (rather than wrapping each
             * one in a ProcessMill job) so the batch actually waits for each
             * mill's full chain to finish before then()/finally() fire — see
             * ProcessMill::jobChain()'s docblock for why the wrapper doesn't.
             *
             * $this->allowFailures also has to reach each job individually —
             * see the docblock on any of the chain jobs (e.g. GeocodeMill)
             * for why the Batch's own allowFailures() isn't enough on its own.
             */
            $jobChains[] = ProcessMill::jobChain($mill, $this->allowFailures);
        }

        $pendingBatch = Bus::batch($jobChains)
            ->before(static function (Batch $batch) use ($importId) {
                Log::debug(self::class.": Before Batch #{$batch->id} processing Import #{$importId}.");
            // })->progress(static function (Batch $batch) use ($importId) {
            //     Log::debug(self::class.": a single job for Batch #{$batch->id} and Import #{$importId} has completed.");
            })->then(static function (Batch $batch) use ($importId) {
                Log::debug(self::class." finished all jobs in Batch #{$batch->id} for Import #{$importId} with no failures.");
            })->catch(static function (Batch $batch, Throwable $e) use ($importId) {
                /**
                 * With allowFailures: false (the spreadsheet-import default),
                 * a chain job's real failure lands here and cancels the
                 * batch — the mill's own failed chain permanently orphans
                 * the rest of its jobs in the batch's pending count, so
                 * finally() below will never fire for this run. Mark the
                 * import Failed here instead of leaving it stuck at
                 * Processing forever.
                 */
                Log::error(self::class.": exception during Batch #{$batch->id} for Import #{$importId}: ", ['msg' => $e->getMessage()]);

                Import::find($importId)?->update([
                    'status' => ImportStatus::Failed,
                    'errors' => $e->getMessage(),
                ]);
            })->finally(static function (Batch $batch) use ($importId, $stateId, $deleteFromState) {
                /**
                 * finally() fires once the batch is done — success or not —
                 * unlike then(), which only fires when every job succeeded.
                 * The next stage needs to run regardless of per-row failures.
                 */
                Log::debug(self::class.": Finally for batch #{$batch->id} and Import #{$importId}.");
                FinalizeMillImport::dispatch($importId, $stateId, $deleteFromState);
            });

        if ($this->allowFailures) {
            $pendingBatch->allowFailures();
        }

        $pendingBatch->dispatch();
    }
}
