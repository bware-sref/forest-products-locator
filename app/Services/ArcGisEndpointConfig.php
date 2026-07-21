<?php

namespace App\Services;

use App\Mappers\MillMapperInterface;
use RuntimeException;

/**
 * Resolves and validates one endpoint's configuration from config/arcgis.php
 * exactly once. Replaces the config("arcgis.endpoints.{$key}...") lookups
 * that were previously duplicated across the service, jobs, and commands.
 */
final class ArcGisEndpointConfig
{
    public function __construct(
        public readonly string $key,
        public readonly string $url,
        public readonly string $description,
        public readonly string $disk,
        public readonly int $timeoutSeconds,
        public readonly array $params,
        public readonly ?string $mapperClass,
        public readonly string $slug,
        public readonly string $geojsonPath,
        public readonly string $csvPath,
    ) {}

    /**
     * @throws RuntimeException When the endpoint is not configured or has no URL.
     */
    public static function fromKey(string $key): self
    {
        $config = config("arcgis.endpoints.{$key}");

        if (blank($config)) {
            throw new RuntimeException(
                "ArcGIS endpoint \"{$key}\" is not defined in config/arcgis.php."
            );
        }

        if (blank($config['url'] ?? null)) {
            throw new RuntimeException(
                "ArcGIS endpoint \"{$key}\" is missing a 'url' value."
            );
        }

        $slug      = $config['slug'] ?? $key;
        $timestamp = now()->format(config('arcgis.timestamp_format', 'Y-m-d\TH-i-s'));

        return new self(
            key:            $key,
            url:            rtrim($config['url'], '/'),
            description:    $config['description'] ?? $key,
            disk:           $config['disk'] ?? config('arcgis.default_disk', 'local'),
            timeoutSeconds: config('arcgis.timeout', 30),
            params:         $config['params'] ?? [],
            mapperClass:    $config['mapper'] ?? null,
            slug:           $slug,
            geojsonPath:    $config['geojson'] ?? self::formatFileName($slug, 'geojson', $timestamp),
            csvPath:        $config['csv']     ?? self::formatFileName($slug, 'csv', $timestamp),
        );
    }

    /**
     * Resolve the mapper for this endpoint.
     *
     * @throws RuntimeException When no valid mapper is configured.
     */
    public function mapper(): MillMapperInterface
    {
        if (blank($this->mapperClass) || ! class_exists($this->mapperClass)) {
            throw new RuntimeException(
                "No valid mapper configured for endpoint \"{$this->key}\". "
                . "Add a 'mapper' key to config/arcgis.php for this endpoint."
            );
        }

        return app($this->mapperClass);
    }

    private static function formatFileName(string $slug, string $extension, string $timestamp): string
    {
        $pattern    = config('arcgis.file_name_pattern', ':export_path:'.DIRECTORY_SEPARATOR.':timestamp:__:slug:.:extension:');
        $exportPath = config('arcgis.export_path', 'arcgis');

        return strtr($pattern, [
            ':export_path:' => $exportPath,
            ':timestamp:'   => $timestamp,
            ':slug:'        => $slug,
            ':extension:'   => $extension,
        ]);
    }
}
