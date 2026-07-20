<?php

namespace App\Jobs;

use App\Enums\MillRawImportStatus;
use App\Enums\PublicationStatus;
use App\Mappers\MillMapperInterface;
use App\Models\Import;
use App\Models\Mill;
use App\Models\MillRawImport;
use App\Services\ArcGisEndpointConfig;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Creates a mill_raw_imports row and a pending mills row for one ArcGIS
 * feature. Runs as part of the batch dispatched by ProcessArcGisImport.
 *
 * Errors are logged and swallowed rather than thrown, so one malformed
 * feature doesn't take down the rest of the import.
 */
class CreateMillFromArcGisFeature implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private readonly Import $import,
        private readonly string $endpoint,
        private readonly array $feature,
    ) {}

    public function handle(): void
    {
        if ($this?->batch()?->cancelled()) {
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->import->id.' was cancelled?');
            return;
        }

        try {
            $mapper    = $this->resolveMapper();
            $rawImport = $this->createRawImport();
            $mill      = $this->createMill($mapper, $rawImport);

            $rawImport->update(['mill_id' => $mill->id]);
            /**
             * We need to increment $import->imported_rows after we create the mill record.
             */
            $this->import->increment('imported_rows');
        } catch (Throwable $e) {
            $this->import->increment('failed_rows');
            Log::error('CreateMillFromArcGisFeature: failed to process feature', [
                'import_id'      => $this->import->id,
                'raw_feature_id' => $this->rawFeatureId(),
                'error'          => $e->getMessage(),
            ]);
        }
    }

    // -------------------------------------------------------------------------

    private function resolveMapper(): MillMapperInterface
    {
        return ArcGisEndpointConfig::fromKey($this->endpoint)->mapper();
    }

    private function createRawImport(): MillRawImport
    {
        return MillRawImport::create([
            'import_id'      => $this->import->id,
            'raw_feature_id' => $this->rawFeatureId(),
            'geojson'        => $this->feature,
            'mill_id'        => null,
            'status'         => MillRawImportStatus::Pending,
        ]);
    }

    private function createMill(MillMapperInterface $mapper, MillRawImport $rawImport): Mill
    {
        $attributes = $mapper->map($this->feature);

        $attributes['state_id']           = $this->import->state_id;
        $attributes['import_id']          = $this->import->id;
        $attributes['mill_raw_import_id'] = $rawImport->id;
        $attributes['status']             = PublicationStatus::Pending;

        return Mill::create($attributes);
    }

    /**
     * Extract the raw feature ID from a GeoJSON feature.
     * Prefers the top-level 'id' attribute (matches OBJECTID/FID in ArcGIS).
     */
    private function rawFeatureId(): ?string
    {
        if (isset($this->feature['id'])) {
            return (string) $this->feature['id'];
        }

        $props = $this->feature['properties'] ?? [];

        foreach (['OBJECTID', 'objectid', 'FID', 'fid'] as $key) {
            if (isset($props[$key])) {
                return (string) $props[$key];
            }
        }

        return null;
    }
}
