<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Writes an already-fetched Collection of GeoJSON features to storage as
 * GeoJSON and/or CSV. Fetching (ArcGisFeatureService) and config resolution
 * (ArcGisEndpointConfig) are separate concerns — this class only writes.
 */
class ArcGisFeatureExporter
{
    private const LAT_LONG_KEYS = [
        'Latitude',
        'Longitude',
    ];

    /**
     * Serialise a Collection of GeoJSON features and persist via Storage.
     *
     * @param  Collection<int, array<string, mixed>>  $features
     */
    public function writeGeoJson(Collection $features, string $path, string $disk = 'local'): void
    {
        $json = json_encode(
            ['type' => 'FeatureCollection', 'features' => $features->values()->all()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        Storage::disk($disk)->put($path, $json);

        Log::info('ArcGisFeatureExporter: GeoJSON written', [
            'path'  => $path,
            'disk'  => $disk,
            'count' => $features->count(),
        ]);
    }

    /**
     * Build a CSV from a Collection of GeoJSON features and persist via Storage.
     *
     * Headers are derived dynamically from the first feature's property keys,
     * so this works regardless of the layer's schema. Longitude/Latitude
     * columns are appended from geometry.coordinates unless the properties
     * already contain them (case-insensitive).
     *
     * @param  Collection<int, array<string, mixed>>  $features
     */
    public function writeCsv(Collection $features, string $path, string $disk = 'local'): void
    {
        if ($features->isEmpty()) {
            Log::warning('ArcGisFeatureExporter: no features to write as CSV', [
                'path' => $path,
                'disk' => $disk,
            ]);
            return;
        }

        $propertyKeys    = array_keys($features->first()['properties'] ?? []);
        $needsLatAndLong = ! $this->hasLatAndLong($propertyKeys);
        $headers         = $needsLatAndLong ? [...$propertyKeys, ...self::LAT_LONG_KEYS] : $propertyKeys;

        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, fields: $headers, separator: ',', enclosure: '"', escape: '');

        foreach ($features as $feature) {
            $row = $feature['properties'] ?? [];

            if ($needsLatAndLong) {
                $coords = $feature['geometry']['coordinates'] ?? [null, null];
                $row    = [...array_values($row), ...$coords];
            }

            $row = collect($row)->map(fn ($item) => Str::trim($item ?? ''))->all();

            fputcsv($handle, fields: $row, separator: ',', enclosure: '"', escape: '');
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::disk($disk)->put($path, $csv);

        Log::info('ArcGisFeatureExporter: CSV written', [
            'path'  => $path,
            'disk'  => $disk,
            'count' => $features->count(),
        ]);
    }

    /**
     * Write both GeoJSON and CSV from a single already-fetched Collection.
     *
     * @param  Collection<int, array<string, mixed>>  $features
     */
    public function writeAll(Collection $features, string $geojsonPath, string $csvPath, string $disk = 'local'): void
    {
        $this->writeGeoJson($features, $geojsonPath, $disk);
        $this->writeCsv($features, $csvPath, $disk);
    }

    private function hasLatAndLong(array $properties): bool
    {
        $properties = collect($properties)->map(fn ($item) => Str::lower($item));

        foreach (self::LAT_LONG_KEYS as $key) {
            if ($properties->doesntContain(Str::lower($key))) {
                return false;
            }
        }

        return true;
    }
}
