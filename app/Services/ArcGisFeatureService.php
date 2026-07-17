<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Fetches all pages of features from an ArcGIS FeatureServer/MapServer
 * layer. Writing the results anywhere is ArcGisFeatureExporter's job.
 */
class ArcGisFeatureService
{
    /**
     * Query parameters sent with every request.
     * Per-endpoint params (from ArcGisEndpointConfig::$params) are merged
     * over these, so any key here can be overridden per endpoint.
     */
    private const DEFAULT_PARAMS = [
        'where'         => '1=1',
        'outFields'     => '*',
        'f'             => 'geojson',
        'orderByFields' => 'OBJECTID',
    ];

    private const PAGE_SIZE = 2000;

    public function __construct(
        private readonly ArcGisEndpointConfig $config,
    ) {}

    /**
     * @throws RuntimeException When the endpoint key is not found in config.
     */
    public static function fromConfig(string $endpoint): static
    {
        return new static(ArcGisEndpointConfig::fromKey($endpoint));
    }

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
            $features = [...$features, ...$page];
            $offset  += self::PAGE_SIZE;

            Log::info('ArcGisFeatureService: page fetched', [
                'endpoint'      => $this->config->key,
                'offset'        => $offset - self::PAGE_SIZE,
                'page_count'    => \count($page),
                'running_total' => \count($features),
            ]);
        } while (\count($page) === self::PAGE_SIZE);

        return collect($features);
    }

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
        $params = array_merge(
            self::DEFAULT_PARAMS,
            $this->config->params,
            [
                'resultOffset'      => $offset,
                'resultRecordCount' => self::PAGE_SIZE,
            ],
        );

        try {
            $response = Http::timeout($this->config->timeoutSeconds)
                ->get("{$this->config->url}/query", $params);
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
}
