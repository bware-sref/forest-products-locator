<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Mill;
use App\Services\ArcGisEndpointConfig;
use App\Services\ArcGisFeatureExporter;
use App\Services\ArcGisFeatureService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Fetches mill data from an ArcGIS FeatureServer, saves the raw GeoJSON
 * response to storage, creates the imports record, and dispatches
 * ProcessArcGisImport to handle the rest.
 *
 * Replaces the synchronous fetch previously performed by
 * ArcGisImportCommand, so the command can dispatch and return immediately.
 */
class FetchArcGisFeaturesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        private readonly string $endpoint,
        private readonly int $stateId,
    ) {}

    public function handle(): void
    {
        try {
            $config   = ArcGisEndpointConfig::fromKey($this->endpoint);
            $features = (new ArcGisFeatureService($config))->fetchAll();
            $path     = $this->storagePath();

            (new ArcGisFeatureExporter())->writeGeoJson($features, $path, $config->disk);

            $import = Import::create([
                'state_id'           => $this->stateId,
                'file_path'          => $path,
                /**
                 * original_file_name is now nullable with default null!
                 */
                // 'original_file_name' => null,
                /**
                 * However, we left out model, which is required.
                 * I'm curious how all the tests passed if we weren't supplying model when creating the records.
                 */
                'model' => Mill::class,
                'api_url'            => $this->buildApiUrl($config),
                'status'             => ImportStatus::Pending,
                'total_rows'         => $features->count(),
            ]);

            Log::info('FetchArcGisFeaturesJob: fetched and stored features', [
                'endpoint'  => $this->endpoint,
                'import_id' => $import->id,
                'count'     => $features->count(),
                'path'      => $path,
            ]);

            ProcessArcGisImport::dispatch($import, $this->endpoint);
        } catch (Throwable $e) {
            Log::error('FetchArcGisFeaturesJob: failed to fetch/save features', [
                'endpoint' => $this->endpoint,
                'error'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    // -------------------------------------------------------------------------

    private function buildApiUrl(ArcGisEndpointConfig $config): string
    {
        return $config->url . '/query?' . http_build_query([
            'where'             => '1=1',
            'outFields'         => '*',
            'f'                 => 'geojson',
            'orderByFields'     => 'OBJECTID',
            'resultOffset'      => 0,
            'resultRecordCount' => 2000,
        ]);
    }

    private function storagePath(): string
    {
        $filename = now()->format('Y-m-d_His') . '_' . $this->endpoint . '.geojson';

        return 'imports/arcgis/' . $this->endpoint . '/' . $filename;
    }
}
