<?php

use App\Services\ArcGisEndpointConfig;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    // Freeze time so the config-resolved default file paths (which embed a
    // timestamp) match between this test and the command's own resolution.
    Carbon\Carbon::setTestNow(now());

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

it('resolves paths and disk from config and writes both files', function () {
    Http::fake(['*/query*' => Http::response(geojsonResponse([georgiaFeature()]))]);

    $config = ArcGisEndpointConfig::fromKey('ga');

    $this->artisan('arcgis:export', ['endpoint' => 'ga'])->assertSuccessful();

    Storage::disk('fake')->assertExists($config->geojsonPath);
    Storage::disk('fake')->assertExists($config->csvPath);
});

it('works independently for each configured endpoint', function () {
    Http::fake(['*/query*' => Http::response(geojsonResponse([northCarolinaFeature()]))]);

    $config = ArcGisEndpointConfig::fromKey('nc');

    $this->artisan('arcgis:export', ['endpoint' => 'nc'])->assertSuccessful();

    Storage::disk('fake')->assertExists($config->geojsonPath);
    Storage::disk('fake')->assertExists($config->csvPath);
});

it('does not throw when geojson/csv paths are missing from config', function () {
    Http::fake(['*/query*' => Http::response(geojsonResponse([georgiaFeature()]))]);

    $this->artisan('arcgis:export', ['endpoint' => 'no_paths'])->assertSuccessful();
});
