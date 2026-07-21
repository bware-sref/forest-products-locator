<?php

use App\Services\ArcGisFeatureExporter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('fake');
});

// ---------------------------------------------------------------------------
// writeGeoJson()
// ---------------------------------------------------------------------------

describe('writeGeoJson()', function () {

    it('writes a valid GeoJSON file to storage', function () {
        $features = collect([georgiaFeature(1, 'Mill A')]);

        (new ArcGisFeatureExporter())->writeGeoJson($features, 'arcgis/test.geojson', 'fake');

        Storage::disk('fake')->assertExists('arcgis/test.geojson');

        $parsed = json_decode(Storage::disk('fake')->get('arcgis/test.geojson'), true);

        expect($parsed['type'])->toBe('FeatureCollection')
            ->and($parsed['features'])->toHaveCount(1)
            ->and($parsed['features'][0]['properties']['MillName'])->toBe('Mill A');
    });

});

// ---------------------------------------------------------------------------
// writeCsv() — schema adaptability
// ---------------------------------------------------------------------------

describe('writeCsv()', function () {

    it('writes a CSV whose headers match the georgiaFeature schema', function () {
        $features = collect([georgiaFeature()]);

        (new ArcGisFeatureExporter())->writeCsv($features, 'arcgis/mills.csv', 'fake');

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
        $features = collect([northCarolinaFeature()]);

        (new ArcGisFeatureExporter())->writeCsv($features, 'arcgis/processors.csv', 'fake');

        $lines   = explode("\n", trim(Storage::disk('fake')->get('arcgis/processors.csv')));
        $headers = str_getcsv($lines[0]);

        expect($headers)->toContain('Company')
            ->and($headers)->toContain('OP1')
            ->and($headers)->toContain('Longitude')
            ->and($headers)->toContain('Latitude')
            // Ensure georgia-mills-specific fields are NOT present
            ->and($headers)->not->toContain('MillName')
            ->and($headers)->not->toContain('County');
    });

    it('appends geometry coordinates as Longitude and Latitude columns', function () {
        $features = collect([georgiaFeature()]);

        (new ArcGisFeatureExporter())->writeCsv($features, 'arcgis/coords.csv', 'fake');

        $lines  = explode("\n", trim(Storage::disk('fake')->get('arcgis/coords.csv')));
        $data   = str_getcsv($lines[1]);
        $header = str_getcsv($lines[0]);

        $lonIdx = array_search('Longitude', $header);
        $latIdx = array_search('Latitude', $header);

        expect($data[$lonIdx])->toBe('-83.5')
            ->and($data[$latIdx])->toBe('33.1');
    });

    it('does not write a CSV file when there are no features', function () {
        Log::spy();

        (new ArcGisFeatureExporter())->writeCsv(collect(), 'arcgis/empty.csv', 'fake');

        Storage::disk('fake')->assertMissing('arcgis/empty.csv');

        Log::shouldHaveReceived('warning')
            ->once()
            ->withArgs(fn ($msg) => str_contains($msg, 'no features'));
    });

});

// ---------------------------------------------------------------------------
// writeAll()
// ---------------------------------------------------------------------------

describe('writeAll()', function () {

    it('writes both GeoJSON and CSV from the same Collection', function () {
        $features = collect([georgiaFeature()]);

        (new ArcGisFeatureExporter())->writeAll(
            $features,
            'arcgis/out.geojson',
            'arcgis/out.csv',
            'fake',
        );

        Storage::disk('fake')->assertExists('arcgis/out.geojson');
        Storage::disk('fake')->assertExists('arcgis/out.csv');
    });

});
