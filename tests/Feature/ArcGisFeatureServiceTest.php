<?php

use App\Services\ArcGisFeatureService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

// ---------------------------------------------------------------------------
// Helpers — fixture factories
// ---------------------------------------------------------------------------

/**
 * Build a minimal GeoJSON FeatureCollection response body.
 *
 * @param  array<int, array<string, mixed>>  $features
 */
function geojsonResponse(array $features): array
{
    return [
        'type'     => 'FeatureCollection',
        'features' => $features,
    ];
}

/**
 * A feature matching the "georgia-mills" schema.
 * Jesus, this is so fucking stupid.
 * Shouldn't we use some sort of data provider for this?
 */
function georgiaFeature(int $objectId = 1, string $millName = 'Test Mill'): array
{
    return [
        'type'       => 'Feature',
        'geometry'   => ['type' => 'Point', 'coordinates' => [-83.5, 33.1]],
        'properties' => [
            'OBJECTID'        => $objectId,
            'MillName'        => $millName,
            'ProductNote'     => null,
            'Product1'        => 'Sawmill - Hardwood',
            'Product2'        => null,
            'Product3'        => null,
            'Symbology'       => 'Sawmill - Hardwood',
            'County'          => 'Clarke',
            'AddressPhysical' => '123 Timber Rd',
            'City'            => 'Athens',
            'State'           => 'GA',
            'Zip'             => '30601',
            'Telephone'       => '706-555-0100',
            'CEO'             => 'Jane Doe',
            'Mill_Type'       => 'Sawmill',
            'Mill_Size'       => 'B',
            'Species'         => 'HW',
            'Latitude'        => 33.1,
            'Longitude'       => -83.5,
        ],
    ];
}

/**
 * A feature matching the "primary_processors" schema.
 */
function northCarolinaFeature(int $objectId = 1, string $company = 'Acme Lumber'): array
{
    return [
        'type'       => 'Feature',
        'geometry'   => ['type' => 'Point', 'coordinates' => [-84.3, 33.7]],
        'properties' => [
            'OBJECTID' => $objectId,
            'Company'  => $company,
            'OP1'      => 'Sawmill, Pine',
            'Lat'      => 33.7,
            'Long'     => -84.3,
        ],
    ];
}

// ---------------------------------------------------------------------------
// Shared config used across tests
// ---------------------------------------------------------------------------

beforeEach(function () {
    /**
     * Why do you recreate the configs instead of just reading them?
     * I get that we're mocking the endpoints, but couldn't we read this from config, then overwrite the URLs?
     */
    Config::set('arcgis', [
        'default_disk' => 'fake',
        'timeout'      => 30,
        /**
         * add our new configs
         */
        'export_path' => 'arcgis',
        /**
         * or should it be endpoint.slug (or just slug)?
         */
        'file_name_pattern' => ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:',
        'timestamp_format' => 'Y-m-d\TH-i-s',

        'endpoints'    => [
            'ga' => [
                'url'         => 'https://services2.arcgis.com/fake/FeatureServer/0',
                'description' => 'Georgia Wood Mills',
                'disk'        => 'fake',
                'slug' => 'georgia',
                // 'geojson'     => 'arcgis/georgia-mills.geojson',
                // 'csv'         => 'arcgis/georgia-mills.csv',
            ],
            'nc' => [
                'url'         => 'https://services6.arcgis.com/fake/FeatureServer/1',
                'description' => 'North Carolina Primary Processors',
                'disk'        => 'fake',
                'slug' => 'north-carolina',
                // 'geojson'     => 'arcgis/north-carolina-mills.geojson',
                // 'csv'         => 'arcgis/north-carolina-mills.csv',
            ],
            'no_paths' => [
                'url'  => 'https://services.arcgis.com/fake/FeatureServer/0',
                'disk' => 'fake',
                'slug' => 'nope',
                // intentionally missing 'geojson' and 'csv'
            ],
        ],
    ]);

    Storage::fake('fake');
});

// ---------------------------------------------------------------------------
// fromConfig()
// ---------------------------------------------------------------------------

describe('fromConfig()', function () {

    it('creates an instance from a valid config key', function () {
        $service = ArcGisFeatureService::fromConfig('ga');
        expect($service)->toBeInstanceOf(ArcGisFeatureService::class);
    });

    it('throws when the endpoint key does not exist', function () {
        ArcGisFeatureService::fromConfig('nonexistent');
    })->throws(RuntimeException::class, 'not defined in config/arcgis.php');

    it('throws when the endpoint url is blank', function () {
        Config::set('arcgis.endpoints.ga.url', '');
        ArcGisFeatureService::fromConfig('ga');
    })->throws(RuntimeException::class, "missing a 'url' value");

});

// ---------------------------------------------------------------------------
// fetchAll() — georgia-mills schema
// ---------------------------------------------------------------------------

describe('fetchAll() with georgia-mills schema', function () {

    it('returns all features from a single page', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([
                georgiaFeature(1, 'Mill A'),
                georgiaFeature(2, 'Mill B'),
            ])),
        ]);

        $service  = new ArcGisFeatureService($url);
        $features = $service->fetchAll();

        expect($features)->toHaveCount(2)
            ->and($features[0]['properties']['MillName'])->toBe('Mill A')
            ->and($features[1]['properties']['MillName'])->toBe('Mill B');
    });

    it('paginates until a page smaller than PAGE_SIZE is returned', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        // Simulate a full first page (2000 records) and a partial second page.
        $page1 = array_map(fn($i) => georgiaFeature($i, "Mill {$i}"), range(1, 2000));
        $page2 = [georgiaFeature(2001, 'Last Mill')];

        Http::fake([
            "{$url}/query*" => Http::sequence()
                ->push(geojsonResponse($page1))
                ->push(geojsonResponse($page2)),
        ]);

        $service  = new ArcGisFeatureService($url);
        $features = $service->fetchAll();

        expect($features)->toHaveCount(2001)
            ->and($features->last()['properties']['MillName'])->toBe('Last Mill');
    });

    it('passes correct pagination parameters in each request', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([])),
        ]);

        (new ArcGisFeatureService($url))->fetchAll();

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

            // dump($params);

            return $params['where']             === '1=1'
                && $params['outFields']         === '*'
                && $params['f']                 === 'geojson'
                && $params['orderByFields']     === 'OBJECTID'
                && $params['resultOffset']      === '0'
                && $params['resultRecordCount'] === '2000';
        });
    })->group('jerks');

});

// ---------------------------------------------------------------------------
// fetchAll() — northCarolinaFeature schema
// ---------------------------------------------------------------------------

describe('fetchAll() with northCarolinaFeature schema', function () {

    it('returns features with the leaner processor schema', function () {
        $url = 'https://services6.arcgis.com/fake/FeatureServer/1';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([
                northCarolinaFeature(1, 'Acme Lumber'),
                northCarolinaFeature(2, 'Beta Plywood'),
            ])),
        ]);

        $service  = new ArcGisFeatureService($url);
        $features = $service->fetchAll();

        expect($features)->toHaveCount(2)
            ->and($features[0]['properties']['Company'])->toBe('Acme Lumber')
            ->and($features[0]['properties']['OP1'])->toBe('Sawmill, Pine')
            ->and($features[1]['properties']['Company'])->toBe('Beta Plywood');
    });

});

// ---------------------------------------------------------------------------
// fetchAll() — error handling
// ---------------------------------------------------------------------------

describe('fetchAll() error handling', function () {

    it('throws on a non-2xx HTTP response', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake(["{$url}/query*" => Http::response([], 500)]);

        (new ArcGisFeatureService($url))->fetchAll();
    })->throws(RuntimeException::class, 'HTTP 500');

    it('throws when the API returns an error payload', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response([
                'error' => ['code' => 400, 'message' => 'Invalid query'],
            ]),
        ]);

        (new ArcGisFeatureService($url))->fetchAll();
    })->throws(RuntimeException::class, 'Invalid query');

    it('throws on a connection exception', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => fn() => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        (new ArcGisFeatureService($url))->fetchAll();
    })->throws(RuntimeException::class, 'connection failed');

});

// ---------------------------------------------------------------------------
// exportGeoJson()
// ---------------------------------------------------------------------------

describe('exportGeoJson()', function () {

    it('writes a valid GeoJSON file to storage', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([
                georgiaFeature(1, 'Mill A'),
            ])),
        ]);

        (new ArcGisFeatureService($url))->exportGeoJson('arcgis/test.geojson', 'fake');

        Storage::disk('fake')->assertExists('arcgis/test.geojson');

        $parsed = json_decode(Storage::disk('fake')->get('arcgis/test.geojson'), true);

        expect($parsed['type'])->toBe('FeatureCollection')
            ->and($parsed['features'])->toHaveCount(1)
            ->and($parsed['features'][0]['properties']['MillName'])->toBe('Mill A');
    });

});

// ---------------------------------------------------------------------------
// exportCsv() — schema adaptability
// ---------------------------------------------------------------------------

describe('exportCsv()', function () {

    it('writes a CSV whose headers match the georgiaFeature schema', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([georgiaFeature()])),
        ]);

        (new ArcGisFeatureService($url))->exportCsv('arcgis/mills.csv', 'fake');

        Storage::disk('fake')->assertExists('arcgis/mills.csv');

        $lines   = explode("\n", trim(Storage::disk('fake')->get('arcgis/mills.csv')));
        $headers = str_getcsv($lines[0]);

        /**
         * Georgia data already contains Longitude and Latitude, ass!
         */
        expect($headers)->toContain('MillName')
            ->and($headers)->toContain('County')
            ->and($headers)->toContain('Mill_Size')
            ->and($headers)->toContain('Longitude')
            ->and($headers)->toContain('Latitude');
    });

    it('writes a CSV whose headers match the northCarolinaFeature schema', function () {
        $url = 'https://services6.arcgis.com/fake/FeatureServer/1';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([northCarolinaFeature()])),
        ]);

        (new ArcGisFeatureService($url))->exportCsv('arcgis/processors.csv', 'fake');

        $lines   = explode("\n", trim(Storage::disk('fake')->get('arcgis/processors.csv')));
        $headers = str_getcsv($lines[0]);

        expect($headers)->toContain('Company')
            ->and($headers)->toContain('OP1')
            // I updated the headings to have ucfirst
            ->and($headers)->toContain('Longitude')
            ->and($headers)->toContain('Latitude')
            // Ensure georgia-mills-specific fields are NOT present
            ->and($headers)->not->toContain('MillName')
            ->and($headers)->not->toContain('County');
    });

    it('appends geometry coordinates as Longitude and Latitude columns', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([georgiaFeature()])),
        ]);

        (new ArcGisFeatureService($url))->exportCsv('arcgis/coords.csv', 'fake');

        $lines  = explode("\n", trim(Storage::disk('fake')->get('arcgis/coords.csv')));
        $data   = str_getcsv($lines[1]);
        $header = str_getcsv($lines[0]);

        // I updated the tack on columns because Georgia already included the damn columns, but with ucfirst
        $lonIdx = array_search('Longitude', $header);
        $latIdx = array_search('Latitude',  $header);

        expect($data[$lonIdx])->toBe('-83.5')
            ->and($data[$latIdx])->toBe('33.1');
    });

    it('does not write a CSV file when there are no features', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([])),
        ]);

        /**
         * Log::shouldReceive() mocking method conflicts with actual Log facade usage in the class we're testing.
         * Gemini says to use Log::fake() or Log::partialMock() instead of Log::shouldReceive().
         * With Log::fake(), after the code we're testing executes and writes log messages, 
         * we use Log::assertLogged() to verify the mocked logs.
         * With Log::partialMock(), we just chain it before shouldReceive() and off to the races.
         * Except partialMock() doesn't work.
         * And Log::fake() triggers an undefined method exception...
         * 
         * So instead we use spy(), then inspect the logger's recent activity with shouldHaveReceived(), which chains
         * the same methods as shouldReceive().
         */
        // Log::shouldReceive('warning')->once()->withArgs(
        //     fn($msg) => str_contains($msg, 'no features')
        // );

        Log::spy();

        (new ArcGisFeatureService($url))->exportCsv('arcgis/empty.csv', 'fake');

        Storage::disk('fake')->assertMissing('arcgis/empty.csv');

        /**
         * Inspect the logger's recent activity with shouldHaveReceived()
         */
        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(
                fn($msg) => str_contains($msg, 'no features')
            );
    });

});

// ---------------------------------------------------------------------------
// exportAll()
// ---------------------------------------------------------------------------

describe('exportAll()', function () {

    it('writes both GeoJSON and CSV in a single fetch', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([georgiaFeature()])),
        ]);

        (new ArcGisFeatureService($url))->exportAll(
            'arcgis/out.geojson',
            'arcgis/out.csv',
            'fake',
        );

        Storage::disk('fake')->assertExists('arcgis/out.geojson');
        Storage::disk('fake')->assertExists('arcgis/out.csv');

        // Only one HTTP request should have been made (fetch-once strategy).
        Http::assertSentCount(1);
    });

});

// ---------------------------------------------------------------------------
// exportFromConfig()
// ---------------------------------------------------------------------------

describe('exportFromConfig()', function () {

    it('resolves paths and disk from config and writes both files', function () {
        Http::fake([
            '*/query*' => Http::response(geojsonResponse([georgiaFeature()])),
        ]);

        /**
         * We changed the stupid fucking endpoint keys that Claude came up with, but we didn't do that here.
         * Also, store the service object so we can use its methods to get the correct file names
         */
        $arc = ArcGisFeatureService::fromConfig('ga');
        $arc->exportFromConfig('ga');

        /**
         * I feel like we should be reading these values from config instead of hardcoding them here.
         * Or even better yet, we should use the damn methods to get the correct file names.
         */
        Storage::disk('fake')->assertExists($arc->geojsonFileName('ga')); // 'arcgis/georgia-mills.geojson');
        Storage::disk('fake')->assertExists($arc->csvFileName('ga')); // 'arcgis/georgia-mills.csv');
        // Storage::disk('fake')->assertExists('arcgis/georgia-mills.geojson');
        // Storage::disk('fake')->assertExists('arcgis/georgia-mills.csv');
    });

    it('works independently for each configured endpoint', function () {
        Http::fake([
            '*/query*' => Http::response(geojsonResponse([northCarolinaFeature()])),
        ]);

        /**
         * We changed the stupid fucking endpoint keys that Claude came up with, but we didn't do that here.
         * store the service so we can ask it what the file names are spozta be.
         */
        // ArcGisFeatureService::fromConfig('primary_processors')
        //     ->exportFromConfig('primary_processors');
        $arc = ArcGisFeatureService::fromConfig('nc');
        $arc->exportFromConfig('nc');

        // Storage::disk('fake')->assertExists('arcgis/primary_processors.geojson');
        // Storage::disk('fake')->assertExists('arcgis/primary_processors.csv');
        Storage::disk('fake')->assertExists($arc->geojsonFileName('nc'));
        Storage::disk('fake')->assertExists($arc->csvFileName('nc'));
    });

    /**
     * This test was obsolete because output file names and path are in the damn configs, mofo!
     */
    it('throws no exceptions when output paths are missing from config', function () {
        Http::fake([
            '*/query*' => Http::response(geojsonResponse([georgiaFeature()])),
        ]);

        ArcGisFeatureService::fromConfig('no_paths')
            ->exportFromConfig('no_paths');
    })->throwsNoExceptions();
    // ->throws(RuntimeException::class, "must define both 'geojson' and 'csv'");

});
