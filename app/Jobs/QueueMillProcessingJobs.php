<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs once every mill_raw_imports/mills row has been created for an
 * ArcGIS import (see CreateMillFromArcGisFeature). Builds the per-mill
 * job chain — identical to the spreadsheet import pipeline — and
 * dispatches it as a batch. FinalizeMillImport runs when that completes.
 */
class QueueMillProcessingJobs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly int $importId,
    ) {}

    public function handle(): void
    {
        $import = Import::findOrFail($this->importId);

        $importId        = $import->id;
        $stateId         = $import->state_id;
        $deleteFromState = $import->delete_from_state;

        /**
         * The Mill::pending() scope disables the globally enabled ApprovedScope
         * so freshly-created (status = pending) mills are actually returned.
         */
        $mills = Mill::query()
            ->pending()
            ->where('import_id', $importId)
            ->get();

        if ($mills->isEmpty()) {
            $import->update([
                'status' => ImportStatus::Failed,
                'errors' => 'No features were successfully processed.',
            ]);

            return;
        }

        $jobChains = $mills->map(fn (Mill $mill) => $this->buildJobChain($mill))->all();

        Bus::batch($jobChains)
            ->name("ArcGIS import #{$importId}")
            ->allowFailures()
            ->catch(static function (Batch $batch, Throwable $e) use ($importId) {
                Log::error("ArcGIS import Batch #{$batch->id} failed", [
                    'import_id' => $importId,
                    'error'     => $e->getMessage(),
                ]);

                Import::find($importId)?->update([
                    'status' => ImportStatus::Failed,
                    'errors' => $e->getMessage(),
                ]);
            })
            ->then(static function (Batch $batch) use ($importId, $stateId, $deleteFromState) {
                Log::debug(self::class.": finished all jobs in Batch #{$batch->id} for Import #{$importId}.");
                FinalizeMillImport::dispatch($importId, $stateId, $deleteFromState);
            })
            ->dispatch();
    }

    /**
     * Build the chained job sequence for one mill.
     * Mirrors the existing spreadsheet import job chain exactly.
     */
    private function buildJobChain(Mill $mill): array
    {
        return [
            new GeocodeMill($mill),
            new ProcessMillState($mill),
            new ProcessMillMillTypes($mill),
            new ProcessMillWoodSpecies($mill),
            new UpdateImportProcessedRows($mill),
        ];
    }
}
