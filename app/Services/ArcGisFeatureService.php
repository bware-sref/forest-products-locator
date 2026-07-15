<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ArcGisFeatureService
{
    /**
     * Query parameters sent with every request.
     * 
     * This is dumb because it makes the default params constant.
     * 
     */
    private const DEFAULT_PARAMS = [
        'where'         => '1=1',
        'outFields'     => '*',
        'f'             => 'geojson',
        /**
         * Lousiana uses FID instead of OBJECTID.
         * And because this is a constant, we can't change it.
         * These should really be moved to the config file.
         * And then overridable for each endpoint.
         */
        'orderByFields' => 'OBJECTID',
    ];

    private const LAT_LONG_KEYS = [
        'Latitude',
        'Longitude'
    ];

    private const PAGE_SIZE = 2000;

    /**
     * @param  string  $baseUrl         Full FeatureServer layer URL, no trailing slash.
     * @param  int     $timeoutSeconds  HTTP timeout per request.
     */
    public function __construct(
        private readonly string $baseUrl,
        private readonly int    $timeoutSeconds = 30,
        /**
         * unlike JS's const, readonly means we can't change anything (with the exception of object properties).
         * i.e., it's not just assignment; changing array values is also forbidden, though I guess in php that usually involves
         * assignment.
         */
        private array $params = [],
    ) {
        $this->params = array_merge(self::DEFAULT_PARAMS, $params);
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Instantiate from a named endpoint defined in config/arcgis.php.
     *
     * Usage:
     *   $service = ArcGisFeatureService::fromConfig('ga_wood_mills');
     *
     * @throws RuntimeException When the endpoint key is not found in config.
     */
    public static function fromConfig(string $endpoint): static
    {
        /**
         * @var array
         */
        $config = config("arcgis.endpoints.{$endpoint}");

        if (blank($config)) {
            throw new RuntimeException(
                "ArcGIS endpoint \"{$endpoint}\" is not defined in config/arcgis.php."
            );
        }

        if (blank($config['url'] ?? null)) {
            throw new RuntimeException(
                "ArcGIS endpoint \"{$endpoint}\" is missing a 'url' value."
            );
        }

        /**
         * I think I need to move the merge to the constructor
         * We still need to fetch config[params] here and pass it to the constructor, though
         */
        // $params = [...self::DEFAULT_PARAMS, ...($config['params'] ?? [])];
        // $params = array_merge(self::DEFAULT_PARAMS, $config["params"] ?? []);
        $params = $config["params"] ?? [];

        // dump('params');
        // dd($params);

        /**
         * This locks each instance into a specific endpoint and thus set of configs.
         * So why don't we store those damn configs here?
         */
        return new static(
            baseUrl:        rtrim($config['url'], '/'),
            /**
             * Also, given all the other damn things that Claude put in the configs specific to each endpoint,
             * why not timeout as well?
             * Not all endpoints were created equally...
             */
            timeoutSeconds: config('arcgis.timeout', 30),
            params: $params,
        );
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Fetch all features and return them as a Collection.
     *
     * Each item is a GeoJSON feature array with 'type', 'geometry',
     * and 'properties' keys. The schema of 'properties' depends entirely
     * on the remote layer — no assumptions are made here.
     *
     * @return Collection<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    public function fetchAll(): Collection
    {
        $features = [];
        $offset   = 0;

        do {
            $page     = $this->fetchPage($offset);
            // $features = array_merge($features, $page);
            /**
             * Below is what intelephense means by "replace array_merge with spread operator".
             */
            $features = [...$features, ...$page];
            $offset  += self::PAGE_SIZE;

            Log::info('ArcGisFeatureService: page fetched', [
                'url'           => $this->baseUrl,
                'offset'        => $offset - self::PAGE_SIZE,
                'page_count'    => \count($page),
                'running_total' => \count($features),
            ]);
        } while (\count($page) === self::PAGE_SIZE);

        return collect($features);
    }

    /**
     * Export all features to a GeoJSON file.
     *
     * @param  string  $path  Storage path, e.g. 'arcgis/mills.geojson'
     * @param  string  $disk  Laravel Storage disk name
     *
     * @throws RuntimeException
     */
    public function exportGeoJson(string $path, string $disk = 'local'): void
    {
        $features = $this->fetchAll();
        $this->writeGeoJson($features, $path, $disk);
    }

    /**
     * Export all features to a CSV file.
     * Geometry coordinates are appended as 'longitude' and 'latitude' columns.
     *
     * @param  string  $path  Storage path, e.g. 'arcgis/mills.csv'
     * @param  string  $disk  Laravel Storage disk name
     *
     * @throws RuntimeException
     */
    public function exportCsv(string $path, string $disk = 'local'): void
    {
        $features = $this->fetchAll();
        $this->writeCsv($features, $path, $disk);
    }

    /**
     * Fetch once and write both GeoJSON and CSV — avoids a second HTTP round-trip.
     *
     * @param  string  $geojsonPath  Storage path for the GeoJSON file
     * @param  string  $csvPath      Storage path for the CSV file
     * @param  string  $disk         Laravel Storage disk name
     *
     * @throws RuntimeException
     */
    public function exportAll(
        string $geojsonPath,
        string $csvPath,
        string $disk = 'local',
    ): void {
        $features = $this->fetchAll();
        $this->writeGeoJson($features, $geojsonPath, $disk);
        $this->writeCsv($features, $csvPath, $disk);
    }

    /**
     * Run exportAll using paths and disk resolved from config/arcgis.php.
     *
     * Intended to be called after instantiating via fromConfig():
     *   ArcGisFeatureService::fromConfig('ga_wood_mills')->exportFromConfig('ga_wood_mills');
     *
     * @throws RuntimeException When required config keys are missing.
     */
    public function exportFromConfig(string $endpoint): void
    {
        /**
         * why didn't we store config in the constructor, dickhead?
         */
        $config  = config("arcgis.endpoints.{$endpoint}", []);
        $disk    = $config['disk'] ?? config('arcgis.default_disk', 'local');
        /**
         * FFS, this is so stupid.
         * Let's use slug we just created instead!
         * slug can fall back to the key if it's not present...
         * except we need to handle this BS in the service instead (or as well)
         */
        // $slug = $config['slug'] ?? $endpoint;
        $geojson = $config['geojson'] ?? static::geojsonFileName($endpoint);
        $csv     = $config['csv']     ?? static::csvFileName($endpoint);

        /**
         * This is stupid.
         */
        if (blank($geojson) || blank($csv)) {
            throw new RuntimeException(
                "Endpoint \"{$endpoint}\" must define both 'geojson' and 'csv' output paths in config/arcgis.php."
            );
        }

        $this->exportAll($geojson, $csv, $disk);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Fetch a single page of features from the REST API.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private function fetchPage(int $offset): array
    {
        try {
            /**
             * use configurable params instead of self::DEFAULT_PARAMS
             */
            // dd($this);
            // dump('this params');
            // dump($this->params);
            $queryParams = array_merge($this->params, [
                    'resultOffset'      => $offset,
                    'resultRecordCount' => self::PAGE_SIZE,
                ]);
            // dump('queryParams');
            // dump($queryParams);
            $response = Http::timeout($this->timeoutSeconds)
                ->get("{$this->baseUrl}/query", array_merge($this->params, [
                    'resultOffset'      => $offset,
                    'resultRecordCount' => self::PAGE_SIZE,
                ]));
                // ->get("{$this->baseUrl}/query", array_merge(self::DEFAULT_PARAMS, [
                //     'resultOffset'      => $offset,
                //     'resultRecordCount' => self::PAGE_SIZE,
                // ]));
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                "ArcGIS connection failed (offset {$offset}): {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "ArcGIS returned HTTP {$response->status()} at offset {$offset}. Body: {$response->body()}"
            );
        }

        $data = $response->json();

        if (isset($data['error'])) {
            $msg  = $data['error']['message'] ?? 'Unknown error';
            $code = $data['error']['code']    ?? 0;
            throw new RuntimeException("ArcGIS API error: {$msg} (code {$code})");
        }

        return $data['features'] ?? [];
    }

    /**
     * Serialise a Collection of GeoJSON features and persist via Storage.
     *
     * @param  Collection<int, array<string, mixed>>  $features
     */
    private function writeGeoJson(Collection $features, string $path, string $disk): void
    {
        $json = json_encode(
            ['type' => 'FeatureCollection', 'features' => $features->values()->all()],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );

        Storage::disk($disk)->put($path, $json);

        Log::info('ArcGisFeatureService: GeoJSON written', [
            'path'  => $path,
            'disk'  => $disk,
            'count' => $features->count(),
        ]);
    }

    /**
     * Build a CSV from a Collection of GeoJSON features and persist via Storage.
     *
     * Headers are derived dynamically from the first feature's property keys,
     * so this works regardless of the layer's schema.
     *
     * @param  Collection<int, array<string, mixed>>  $features
     */
    private function writeCsv(Collection $features, string $path, string $disk): void
    {
        if ($features->isEmpty()) {
            Log::warning('ArcGisFeatureService: no features to write as CSV', [
                'path' => $path,
                'disk' => $disk,
            ]);
            return;
        }

        $propertyKeys = array_keys($features->first()['properties'] ?? []);
        /**
         * @MOFO
         * This assumes there is no longitude and latitude in properties.
         * array_merge will overwrite earlier items if duplicate non-numeric keys exist in the later arrays.
         * But since Georgia already has Latitude and Longitude, this will add the lower case version
         */
        $headers = $propertyKeys;
        $needsLatAndLong = ! $this->hasLatAndLong($headers);
        if ($needsLatAndLong) {
            $headers = [...$headers, ...self::LAT_LONG_KEYS];
        }
        // $lAndL = self::LAT_LONG_KEYS; // ['longitude', 'latitude'];
        // $headers      = array_merge($propertyKeys, self::LAT_LONG_KEYS);

        $handle = fopen('php://temp', 'r+');

        /**
         * The default value of the escape parameter, "\\", has been deprecated.
         * It is now recommended to pass an empty string as the value for escape.
         * Why didn't they just make empty string the default value when they deprecated the old one?
         */
        fputcsv(
            $handle,
            fields: $headers,
            separator: ",",
            enclosure: "\"",
            escape: "",
        );

        foreach ($features as $feature) {
            /**
             * we only need to extract geometry.coordinates if we need to add Latitude & Longitude
             */
            $row = $feature['properties'] ?? [];
            if ($needsLatAndLong) {
                $coords = $feature['geometry']['coordinates'] ?? [null, null];
                $row = [...array_values($row), ...$coords];                
            }

            // $coords = $feature['geometry']['coordinates'] ?? [null, null];
            // fputcsv($handle, array_merge(
            //     array_values($feature['properties'] ?? []),
            //     [$coords[0] ?? null, $coords[1] ?? null],
            // ));
            /**
             * The default value of the escape parameter, "\\", has been deprecated.
             * It is now recommended to pass an empty string as the value for escape.
             * Why didn't they just make empty string the default value when they deprecated the old one?
             */
            fputcsv(
                $handle,
                fields: $row,
                separator: ",",
                enclosure: '"',
                escape: '',
            );
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        Storage::disk($disk)->put($path, $csv);

        Log::info('ArcGisFeatureService: CSV written', [
            'path'  => $path,
            'disk'  => $disk,
            'count' => $features->count(),
        ]);
    }

    /**
     * This is so stupid.
     * Should I just make this method public so I don't have to add a wrapper for it?
     * Well, we could do that...or we could make public helpers that include the extension
     */

    public static function formatFileName(string $endpoint, string $extension): string
    {
        $fileNamePattern = config("arcgis.file_name_pattern", ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:');
        $timestampFormat = config("arcgis.timestamp_format", 'Y-m-d\TH-i-s');
        $exportPath = config("arcgis.export_path", 'arcgis');

        $config = config("arcgis.endpoints.{$endpoint}", []);
        $slug = $config['slug'] ?? $endpoint;
        $timestamp = now()->format($timestampFormat);

        $keys = ['export_path', 'timestamp', 'slug', 'extension'];

        $search = collect($keys)
            ->map(fn ($item) => Str::wrap($item, ':', ':'))
            ->toArray();
        $replace = collect($keys)
            ->map(fn ($item) => Str::camel($item))
            ->toArray();
        $replace = compact(...$replace);

        $fileName = Str::replace($search, $replace, $fileNamePattern);
        return $fileName;
    }

    public static function geojsonFileName(string $endpoint): string
    {
        return static::formatFileName($endpoint, 'geojson');
    }

    public static function csvFileName(string $endpoint): string
    {
        return static::formatFileName($endpoint, 'csv');
    }

    private function hasLatAndLong(array $properties): bool
    {
        $properties = collect($properties)
            ->map(fn ($item) => Str::lower($item));

        foreach (self::LAT_LONG_KEYS as $key) {
            if ($properties->doesntContain(Str::lower($key))) {
                return false;
            }
        }
        
        return true;
    }
}
