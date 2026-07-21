<?php

use App\Services\ArcGisFeatureService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

// ---------------------------------------------------------------------------
// Shared config used across tests
// ---------------------------------------------------------------------------

beforeEach(function () {
    Config::set('arcgis', [
        'default_disk' => 'fake',
        'timeout'      => 30,
        'export_path'  => 'arcgis',
        'file_name_pattern' => ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:',
        'timestamp_format'  => 'Y-m-d\TH-i-s',

        'endpoints' => [
            'ga' => [
                'url'         => 'https://services2.arcgis.com/fake/FeatureServer/0',
                'description' => 'Georgia Wood Mills',
                'disk'        => 'fake',
                'slug'        => 'georgia',
            ],
            'nc' => [
                'url'         => 'https://services6.arcgis.com/fake/FeatureServer/1',
                'description' => 'North Carolina Primary Processors',
                'disk'        => 'fake',
                'slug'        => 'north-carolina',
            ],
        ],
    ]);
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

        $features = ArcGisFeatureService::fromConfig('ga')->fetchAll();

        expect($features)->toHaveCount(2)
            ->and($features[0]['properties']['MillName'])->toBe('Mill A')
            ->and($features[1]['properties']['MillName'])->toBe('Mill B');
    });

    it('paginates until a page smaller than PAGE_SIZE is returned', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        // Simulate a full first page (2000 records) and a partial second page.
        $page1 = array_map(fn ($i) => georgiaFeature($i, "Mill {$i}"), range(1, 2000));
        $page2 = [georgiaFeature(2001, 'Last Mill')];

        Http::fake([
            "{$url}/query*" => Http::sequence()
                ->push(geojsonResponse($page1))
                ->push(geojsonResponse($page2)),
        ]);

        $features = ArcGisFeatureService::fromConfig('ga')->fetchAll();

        expect($features)->toHaveCount(2001)
            ->and($features->last()['properties']['MillName'])->toBe('Last Mill');
    });

    it('passes correct pagination parameters in each request', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response(geojsonResponse([])),
        ]);

        ArcGisFeatureService::fromConfig('ga')->fetchAll();

        Http::assertSent(function (Request $request) {
            parse_str(parse_url($request->url(), PHP_URL_QUERY), $params);

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

        $features = ArcGisFeatureService::fromConfig('nc')->fetchAll();

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

        ArcGisFeatureService::fromConfig('ga')->fetchAll();
    })->throws(RuntimeException::class, 'HTTP 500');

    it('throws when the API returns an error payload', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => Http::response([
                'error' => ['code' => 400, 'message' => 'Invalid query'],
            ]),
        ]);

        ArcGisFeatureService::fromConfig('ga')->fetchAll();
    })->throws(RuntimeException::class, 'Invalid query');

    it('throws on a connection exception', function () {
        $url = 'https://services2.arcgis.com/fake/FeatureServer/0';

        Http::fake([
            "{$url}/query*" => fn () => throw new \Illuminate\Http\Client\ConnectionException('timeout'),
        ]);

        ArcGisFeatureService::fromConfig('ga')->fetchAll();
    })->throws(RuntimeException::class, 'connection failed');

});
