<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Services\ArcGisEndpointConfig;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

/**
 * Reads the locally-saved GeoJSON file produced by FetchArcGisFeaturesJob,
 * filters features via the endpoint's mapper, and dispatches a batch of
 * CreateMillFromArcGisFeature jobs — one per feature.
 *
 * ProcessImportedMills takes over once that batch is done (see finally()
 * below — not then(), since a per-feature failure shouldn't block the rest
 * of the import), building the per-mill job chain and triggering
 * FinalizeMillImport. Same downstream job the spreadsheet-import pipeline
 * uses, just dispatched with allowFailures: true.
 */
class ProcessArcGisImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function __construct(
        private readonly Import $import,
        private readonly string $endpoint,
    ) {}

    public function handle(): void
    {
        $this->import->update(['status' => ImportStatus::Processing]);

        /**
         * Local variable so we can use() it with callbacks.
         */
        $importId = $this->import->id;

        try {
            $features = $this->loadFeatures();
            $mapper   = ArcGisEndpointConfig::fromKey($this->endpoint)->mapper();

            $jobs = collect($features)
                ->filter(fn (array $feature) => $mapper->shouldImport($feature))
                ->map(fn (array $feature) => new CreateMillFromArcGisFeature($this->import, $this->endpoint, $feature))
                ->values()
                ->all();

            if (empty($jobs)) {
                $this->import->update([
                    'status' => ImportStatus::Failed,
                    'errors' => 'No features were eligible for import.',
                ]);

                return;
            }

            Bus::batch($jobs)
                ->name("ArcGIS raw import #{$importId} ({$this->endpoint})")
                ->allowFailures()
                ->catch(static function (Batch $batch, Throwable $e) use ($importId) {
                    Log::error(self::class.": ArcGIS raw import Batch #{$batch->id} for Import #{$importId} failed", [
                        'import_id' => $importId,
                        'error'     => $e->getMessage(),
                    ]);

                    /**
                     * Can we append a field value when updating?
                     * Probably not.
                     */
                    Import::find($importId)?->update([
                        'status' => ImportStatus::Failed,
                        'errors' => $e->getMessage(),
                    ]);
                })
                ->then(static function (Batch $batch) use ($importId) {
                    Log::debug(self::class.": finished Batch #{$batch->id} for Import #{$importId} with no failures.");
                })
                ->finally(static function (Batch $batch) use ($importId) {
                    /**
                     * finally() fires once the batch is done — success or not —
                     * unlike then(), which only fires when every job succeeded.
                     * CreateMillFromArcGisFeature swallows per-feature errors into
                     * failed_rows, so the pipeline needs to advance regardless.
                     *
                     * Converges onto the same downstream job the spreadsheet-import
                     * pipeline uses (ProcessImportedMills -> ProcessMill), just with
                     * allowFailures: true since API data always has some bad rows.
                     */
                    Log::debug(self::class.": Finally for batch #{$batch->id} and Import #{$importId}.");
                    ProcessImportedMills::dispatch(Import::findOrFail($importId), allowFailures: true);
                })
                ->dispatch();

        } catch (Throwable $e) {
            $this->import->update([
                'status' => ImportStatus::Failed,
                'errors' => $e->getMessage(),
            ]);

            Log::error('ProcessArcGisImport: top-level failure', [
                'import_id' => $importId,
                'error'     => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Load and decode the GeoJSON feature collection from the local file.
     *
     * @return array<int, array<string, mixed>>
     */
    private function loadFeatures(): array
    {
        $config = ArcGisEndpointConfig::fromKey($this->endpoint);

        $raw = Storage::disk($config->disk)->get($this->import->file_path);

        if ($raw === null) {
            throw new RuntimeException(
                "GeoJSON file not found: {$this->import->file_path}"
            );
        }

        $decoded = json_decode($raw, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to decode GeoJSON: ' . json_last_error_msg());
        }

        return $decoded['features'] ?? [];
    }
}
