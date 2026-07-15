<?php

namespace App\Console\Commands;

use App\Services\ArcGisFeatureService;
use Illuminate\Console\Command;
use Throwable;

class ArcGisExportCommand extends Command
{
    /**
     * php artisan arcgis:export              — exports all configured endpoints
     * php artisan arcgis:export ga_wood_mills — exports one endpoint
     * php artisan arcgis:export --list        — lists available endpoints
     */
    protected $signature = 'arcgis:export
                            {endpoint? : Key of a single endpoint defined in config/arcgis.php}
                            {--list    : List all configured endpoints and exit}';

    protected $description = 'Export ArcGIS FeatureServer layer data to GeoJSON and CSV';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listEndpoints();
        }

        $endpoints = $this->resolveEndpoints();

        if (empty($endpoints)) {
            $this->error('No endpoints are configured in config/arcgis.php.');
            return self::FAILURE;
        }

        $failed = 0;

        foreach ($endpoints as $key) {
            if (empty(config("arcgis.endpoints.{$key}.url"))) {
                $this->newLine();
                $this->warn("Endpoint config '{$key}' does not have a URL defined. Skipping...");
                continue;
            }
            $failed += $this->exportEndpoint($key) ? 0 : 1;
        }

        if ($failed > 0) {
            $this->newLine();
            $this->error("{$failed} endpoint(s) failed. See logs for details.");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info('All exports completed successfully.');
        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    /**
     * Return the list of endpoint keys to process.
     *
     * @return array<int, string>
     */
    private function resolveEndpoints(): array
    {
        $argument  = $this->argument('endpoint');
        $all       = array_keys(config('arcgis.endpoints', []));

        if (blank($argument)) {
            return $all;
        }

        if (! \in_array($argument, $all, strict: true)) {
            $this->error("Endpoint \"{$argument}\" is not defined in config/arcgis.php.");
            $this->line('Run <comment>php artisan arcgis:export --list</comment> to see available endpoints.');
            return [];
        }

        /**
         * Check for empty URL
         */
        $url = config("arcgis.endpoints.{$argument}.url", '');
        if (empty($url)) {
            $this->error("Endpoint \"{$argument}\" does not have a URL defined in config/arcgis.php.");
            return [];
        }

        return [$argument];
    }

    /**
     * Export a single endpoint, returning true on success.
     */
    private function exportEndpoint(string $key): bool
    {
        $config      = config("arcgis.endpoints.{$key}", []);
        $description = $config['description'] ?? $key;

        /**
         * FFS, this is so stupid.
         * The command shouldn't double up on this crap.
         */
        $geojson     = $config['geojson']     ?? ArcGisFeatureService::geojsonFileName($key); // '(not set)';
        $csv         = $config['csv']         ?? ArcGisFeatureService::csvFileName($key); // '(not set)';
        $disk        = $config['disk']        ?? config('arcgis.default_disk', 'local');

        $this->newLine();
        $this->line("<fg=cyan;options=bold>► {$description}</> <fg=gray>[{$key}]</>");
        $this->line("  GeoJSON : {$geojson}");
        $this->line("  CSV     : {$csv}");
        $this->line("  Disk    : {$disk}");

        try {
            $service = ArcGisFeatureService::fromConfig($key);

            $this->output->write('  Fetching');
            $features = $service->fetchAll();
            $count    = $features->count();
            $this->line(" → <info>{$count} features</info>");

            $this->output->write('  Writing GeoJSON');
            $service->exportFromConfig($key);
            $this->line(' → <info>done</info>');

            $this->line("  <fg=green>✓ {$key} exported successfully.</>");
            return true;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error("  Failed to export \"{$key}\": {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Print a table of all configured endpoints and exit.
     */
    private function listEndpoints(): int
    {
        $endpoints = config('arcgis.endpoints', []);

        if (empty($endpoints)) {
            $this->warn('No endpoints configured in config/arcgis.php.');
            return self::SUCCESS;
        }

        $rows = [];
        foreach ($endpoints as $key => $cfg) {
            /**
             * We only want to list the endpoints with URLs defined.
             */
            if (empty($cfg['url'])) {
                continue;
            }
            $rows[] = [
                $key,
                $cfg['description'] ?? '—',
                $cfg['slug'] ?? "{$key} (default)",
                $cfg['geojson']     ?? ArcGisFeatureService::geojsonFileName($key) ?? '—',
                $cfg['csv']         ?? ArcGisFeatureService::csvFileName($key) ?? '—',
                $cfg['disk']        ?? config('arcgis.default_disk', 'local'),
            ];
        }

        $this->table(
            ['Key', 'Description', 'slug', 'GeoJSON path', 'CSV path', 'Disk'],
            $rows,
        );

        return self::SUCCESS;
    }
}
