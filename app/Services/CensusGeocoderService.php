<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Queries the US Census Bureau's Geocoder API.
 * https://geocoding.geo.census.gov/geocoder/Geocoding_Services_API.html
 *
 * Primary usage: oneLineAddress() runs a `onelineaddress` search against the
 * "current" benchmark and returns the `locations` result (addressMatches).
 *
 * Secondary usage: coordinates() runs a `coordinates` search against the
 * "current" benchmark/vintage and returns the `geographies` result.
 *
 * Both return the API's decoded `result` object as-is (not reshaped into
 * app-specific fields, unlike GeocodingService) since addressMatches and
 * geographies carry substantially different, nested data per caller need.
 *
 * addressBatch() and coordinatesBatch() cover the CSV-based batch endpoints
 * (up to 10,000 rows per request) and return parsed CSV rows, since the
 * batch API responds with CSV, not JSON.
 *
 * benchmarks() and vintages() query the API's own reference endpoints,
 * useful since Census periodically retires benchmarks/vintages and the
 * configured defaults will eventually need updating.
 *
 * The default benchmark/vintage come from config('census-geocoder.*'),
 * falling back to BENCHMARK_CURRENT/VINTAGE_CURRENT; pass $benchmark/
 * $vintage to any method to override per-call.
 */
class CensusGeocoderService
{
    /** Falls back to this when config('census-geocoder.benchmark') is unset. */
    public const string BENCHMARK_CURRENT = '4';

    /** Falls back to this when config('census-geocoder.vintage') is unset; pairs with benchmark 4. */
    public const string VINTAGE_CURRENT = '4';

    /** The batch endpoints reject files with more rows than this. */
    public const int MAX_BATCH_ROWS = 10_000;

    /**
     * Look up a single free-form address.
     *
     * @return array{input: array, addressMatches: array}|null Null when the API returns no addressMatches.
     *
     * @throws RuntimeException on connection failure, a non-2xx response, or an unexpected response shape.
     */
    public function oneLineAddress(
        string $address,
        ?string $benchmark = null,
        ?bool $rawOutput = false,
    ): ?array
    {
        $result = $this->request('locations/onelineaddress', [
            'address' => $address,
            'benchmark' => $benchmark ?? $this->benchmark(),
        ]);

        $result = $this->nullIfEmpty($result, 'addressMatches');

        if (true === $rawOutput) {
            return $result;
        }

        return $this->formatGeocodeResult($result);
    }

    /**
     * Reverse-geocode a coordinate pair to its Census geographies.
     *
     * @param string|string[]|null $layers Comma-delimited layer ids/names, an array of them, "all", or null for the API default.
     * @return array{input: array, geographies: array}|null Null when the API returns no geographies.
     *
     * @throws RuntimeException on connection failure, a non-2xx response, or an unexpected response shape.
     */
    public function coordinates(
        int|float|string $longitude,
        int|float|string $latitude,
        ?string $benchmark = null,
        ?string $vintage = null,
        string|array|null $layers = null,
    ): ?array
    {
        $params = [
            // remember: x is longitude, y is latitude
            'x' => (float) $longitude,
            'y' => (float) $latitude,
            'benchmark' => $benchmark ?? $this->benchmark(),
            'vintage' => $vintage ?? $this->vintage(),
        ];

        if (! empty($layers)) {
            $params['layers'] = \is_array($layers) ? implode(',', $layers) : $layers;
        }

        $result = $this->request('geographies/coordinates', $params);

        return $this->nullIfEmpty($result, 'geographies');
    }

    /**
     * Batch geocode up to 10,000 addresses in a single request.
     *
     * @param array<int, array{0: int|string, 1: string, 2?: string, 3?: string, 4?: string}> $rows Each row: [id, street, city, state, zip] — per the API, either zip or city+state is required alongside street.
     * @param 'locations'|'geographies' $returnType
     * @return array<int, array<int, string>> Parsed CSV rows (the API returns no header row). Columns for 'locations': id, input address, match ("Match"/"No_Match"/"Tie"), match type, matched address, coordinates ("lon,lat"), tigerLine id, side. 'geographies' appends geography columns per the resolved layers.
     *
     * @throws RuntimeException
     */
    public function addressBatch(
        array $rows,
        string $returnType = 'locations',
        ?string $benchmark = null,
        ?string $vintage = null,
    ): array
    {
        $params = ['benchmark' => $benchmark ?? $this->benchmark()];

        if ('geographies' === $returnType) {
            $params['vintage'] = $vintage ?? $this->vintage();
        }

        $body = $this->requestBatch(
            "{$returnType}/addressbatch",
            $params,
            'addressFile',
            'addressbatch.csv',
            $this->rowsToCsv($rows),
        );

        return $this->parseCsv($body);
    }

    /**
     * Batch reverse-geocode up to 10,000 coordinate pairs in a single request.
     *
     * @param array<int, array{0: int|string, 1: int|float|string, 2: int|float|string}> $rows Each row: [id, longitude, latitude].
     * @return array<int, array<int, string>> Parsed CSV rows (the API returns no header row): id, input longitude, input latitude, then geography columns per the resolved layers.
     *
     * @throws RuntimeException
     */
    public function coordinatesBatch(
        array $rows,
        ?string $benchmark = null,
        ?string $vintage = null,
    ): array
    {
        $params = [
            'benchmark' => $benchmark ?? $this->benchmark(),
            'vintage' => $vintage ?? $this->vintage(),
        ];

        $body = $this->requestBatch(
            'geographies/coordinatesbatch',
            $params,
            'coordinatesFile',
            'coordinatesbatch.csv',
            $this->rowsToCsv($rows),
        );

        return $this->parseCsv($body);
    }

    /**
     * List all benchmarks the API currently supports.
     *
     * @return array<int, array{isDefault: bool, benchmarkDescription: string, id: string, benchmarkName: string}>
     *
     * @throws RuntimeException on connection failure, a non-2xx response, or an unexpected response shape.
     */
    public function benchmarks(): array
    {
        $data = $this->requestJson($this->endpointPath('benchmarks'), []);

        return $data['benchmarks'] ?? [];
    }

    /**
     * List all vintages available for a given benchmark (defaults to the
     * configured benchmark). Geographies lookups need a vintage that pairs
     * with the benchmark they're run against.
     *
     * @return array<int, array{isDefault: bool, id: string, vintageName: string, vintageDescription: string}>
     *
     * @throws RuntimeException on connection failure, a non-2xx response, or an unexpected response shape.
     */
    public function vintages(?string $benchmark = null): array
    {
        $data = $this->requestJson($this->endpointPath('vintages'), [
            'benchmark' => $benchmark ?? $this->benchmark(),
        ]);

        return $data['vintages'] ?? [];
    }

    /**
     * @throws RuntimeException on connection failure, a non-2xx response, or an unexpected response shape.
     */
    protected function request(string $path, array $params): array
    {
        $data = $this->requestJson($path, $params);

        if (! \is_array($data['result'] ?? null)) {
            Log::warning(self::class.": unexpected response shape from \"{$path}\"", [
                'params' => $params,
                'body' => $data,
            ]);

            throw new RuntimeException("Census Geocoder returned an unexpected response shape for \"{$path}\".");
        }

        return $data['result'];
    }

    /**
     * @throws RuntimeException on connection failure, a non-2xx response, or a non-JSON-object body.
     */
    protected function requestJson(string $path, array $params): array
    {
        $params['format'] = 'json';

        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->get("{$this->baseUrl()}/{$path}", $params);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                "Census Geocoder connection failed for \"{$path}\": {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Census Geocoder returned HTTP {$response->status()} for \"{$path}\". Body: {$response->body()}"
            );
        }

        $data = $response->json();

        if (! \is_array($data)) {
            Log::warning(self::class.": unexpected response shape from \"{$path}\"", [
                'params' => $params,
                'body' => $response->body(),
            ]);

            throw new RuntimeException("Census Geocoder returned an unexpected response shape for \"{$path}\".");
        }

        return $data;
    }

    /**
     * @throws RuntimeException on connection failure or a non-2xx response.
     */
    protected function requestBatch(
        string $path,
        array $params,
        string $fileField,
        string $fileName,
        string $fileContents,
    ): string
    {
        try {
            $response = Http::timeout($this->timeoutSeconds())
                ->attach($fileField, $fileContents, $fileName)
                ->post("{$this->baseUrl()}/{$path}", $params);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                "Census Geocoder connection failed for \"{$path}\": {$e->getMessage()}",
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException(
                "Census Geocoder returned HTTP {$response->status()} for \"{$path}\". Body: {$response->body()}"
            );
        }

        return $response->body();
    }

    /**
     * @param array<int, array<int, int|float|string>> $rows
     *
     * @throws \InvalidArgumentException When $rows exceeds MAX_BATCH_ROWS.
     */
    protected function rowsToCsv(array $rows): string
    {
        if (\count($rows) > self::MAX_BATCH_ROWS) {
            throw new \InvalidArgumentException(
                'Census Geocoder batch requests are limited to '.self::MAX_BATCH_ROWS.' rows, got '.\count($rows).'.'
            );
        }

        $stream = fopen('php://temp', 'r+');

        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }

    /**
     * @return array<int, array<int, string>>
     */
    protected function parseCsv(string $csv): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csv));

        return array_map('str_getcsv', array_filter($lines, fn (string $line) => '' !== $line));
    }

    /**
     * The Census API always returns HTTP 200 with an empty list on a
     * no-match, rather than a 404, so callers get null instead of an
     * empty-but-truthy array.
     */
    protected function nullIfEmpty(array $result, string $key): ?array
    {
        if (empty($result[$key])) {
            return null;
        }

        return $result;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('census-geocoder.base_url', 'https://geocoding.geo.census.gov/geocoder'), '/');
    }

    protected function timeoutSeconds(): int
    {
        return (int) config('census-geocoder.timeout', 15);
    }

    protected function benchmark(): string
    {
        return (string) (config('census-geocoder.benchmark') ?? self::BENCHMARK_CURRENT);
    }

    protected function vintage(): string
    {
        return (string) (config('census-geocoder.vintage') ?? self::VINTAGE_CURRENT);
    }

    protected function endpointPath(string $key): string
    {
        return (string) (config("census-geocoder.endpoints.{$key}") ?? $key);
    }

    protected function formatGeocodeResult(?array $result): array
    {
        if (empty($result)) {
            return [];
        }

        $matches = collect($result['addressMatches'][0] ?? [])
            ->except('tigerLine')
            ->toArray();

        $bits = $matches['addressComponents'] ?? [];
        $xy = $matches['coordinates'] ?? [];
        
        $city = $bits['city'] ?? '';
        $state = $bits['state'] ?? '';
        $zip = $bits['zip'] ?? '';

        return [
            'query' => $result['input']['address']['address'] ?? '',
            'latitude' => $xy['y'] ?? '',
            'longitude' => $xy['x'] ?? '',
            'state' => $state,
            'city' => Str::title($city),
            'zip' => $zip,
            'street_address' => Str::of($matches['matchedAddress'] ?? '')
                ->replace([$city, $state, $zip], '')
                ->trim()
                ->trim(', ')
                ->title()
                ->toString(),
            'addressComponents' => $bits,
        ];
    }
}
