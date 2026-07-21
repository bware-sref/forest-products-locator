<?php

use App\Mappers\GeorgiaMillMapper;
use App\Services\ArcGisEndpointConfig;
use Illuminate\Support\Facades\Config;

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
                'mapper'      => GeorgiaMillMapper::class,
            ],
            'no_paths' => [
                'url'  => 'https://services.arcgis.com/fake/FeatureServer/0',
                'disk' => 'fake',
                'slug' => 'nope',
                // intentionally missing 'geojson', 'csv', 'description', and 'mapper'
            ],
        ],
    ]);
});

// ---------------------------------------------------------------------------
// fromKey()
// ---------------------------------------------------------------------------

describe('fromKey()', function () {

    it('resolves every field from a fully-configured endpoint', function () {
        $config = ArcGisEndpointConfig::fromKey('ga');

        expect($config->key)->toBe('ga')
            ->and($config->url)->toBe('https://services2.arcgis.com/fake/FeatureServer/0')
            ->and($config->description)->toBe('Georgia Wood Mills')
            ->and($config->disk)->toBe('fake')
            ->and($config->slug)->toBe('georgia')
            ->and($config->timeoutSeconds)->toBe(30)
            ->and($config->mapperClass)->toBe(GeorgiaMillMapper::class);
    });

    it('throws when the endpoint key does not exist', function () {
        ArcGisEndpointConfig::fromKey('nonexistent');
    })->throws(RuntimeException::class, 'not defined in config/arcgis.php');

    it('throws when the endpoint url is blank', function () {
        Config::set('arcgis.endpoints.ga.url', '');
        ArcGisEndpointConfig::fromKey('ga');
    })->throws(RuntimeException::class, "missing a 'url' value");

    it('trims a trailing slash from the URL', function () {
        Config::set('arcgis.endpoints.ga.url', 'https://services2.arcgis.com/fake/FeatureServer/0/');

        expect(ArcGisEndpointConfig::fromKey('ga')->url)
            ->toBe('https://services2.arcgis.com/fake/FeatureServer/0');
    });

    it('falls back to the endpoint key for slug and description when not configured', function () {
        $config = ArcGisEndpointConfig::fromKey('no_paths');

        expect($config->slug)->toBe('nope')
            ->and($config->description)->toBe('no_paths');
    });

    it('computes default geojson/csv paths from the file name pattern when not configured', function () {
        $config = ArcGisEndpointConfig::fromKey('no_paths');

        expect($config->geojsonPath)->toContain('__nope.geojson')
            ->and($config->csvPath)->toContain('__nope.csv');
    });

    it('uses explicit geojson/csv paths from config when present', function () {
        Config::set('arcgis.endpoints.ga.geojson', 'arcgis/georgia-mills.geojson');
        Config::set('arcgis.endpoints.ga.csv', 'arcgis/georgia-mills.csv');

        $config = ArcGisEndpointConfig::fromKey('ga');

        expect($config->geojsonPath)->toBe('arcgis/georgia-mills.geojson')
            ->and($config->csvPath)->toBe('arcgis/georgia-mills.csv');
    });

});

// ---------------------------------------------------------------------------
// mapper()
// ---------------------------------------------------------------------------

describe('mapper()', function () {

    it('resolves the configured mapper class', function () {
        expect(ArcGisEndpointConfig::fromKey('ga')->mapper())->toBeInstanceOf(GeorgiaMillMapper::class);
    });

    it('throws when no mapper is configured', function () {
        ArcGisEndpointConfig::fromKey('no_paths')->mapper();
    })->throws(RuntimeException::class, "Add a 'mapper' key");

    it('throws when the configured mapper class does not exist', function () {
        Config::set('arcgis.endpoints.ga.mapper', 'App\\Mappers\\DoesNotExist');

        ArcGisEndpointConfig::fromKey('ga')->mapper();
    })->throws(RuntimeException::class, 'No valid mapper configured');

});
