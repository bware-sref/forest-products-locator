<?php

namespace App\Console\Commands;

use App\Jobs\FetchArcGisFeaturesJob;
use App\Models\State;
use Illuminate\Console\Command;

/**
 * Validates an ArcGIS endpoint and its target state, then dispatches
 * FetchArcGisFeaturesJob to fetch the data and queue it for import.
 *
 * Usage:
 *   php artisan mills:import-arcgis ga_wood_mills
 *   php artisan mills:import-arcgis --list
 */
class ArcGisImportCommand extends Command
{
    protected $signature = 'mills:import-arcgis
                            {endpoint? : Config key from config/arcgis.php}
                            {--L|list    : List configured endpoints and exit}
                            {--A|all : with list, shows incomplete endpoint configs}
                            {--D|delete=true : Delete existing Mills in this state}
                            ';

    protected $description = 'Fetch mill data from an ArcGIS FeatureServer and queue for import';

    public function handle(): int
    {
        if ($this->option('list')) {
            return $this->listEndpoints();
        }

        $endpoint = $this->argument('endpoint');

        if (blank($endpoint)) {
            $this->error('Please provide an endpoint key. Run with --list to see available endpoints.');
            return self::FAILURE;
        }

        /**
         * @var array
         */
        $config = config("arcgis.endpoints.{$endpoint}", []);

        if (blank($config)) {
            $this->error("Endpoint \"{$endpoint}\" is not defined in config/arcgis.php.");
            return self::FAILURE;
        }

        $stateAbbr = strtoupper($endpoint);

        $state = State::where('abbreviation', $stateAbbr)->first();

        if (! $state) {
            $this->error("State \"{$stateAbbr}\" not found in the states table.");
            return self::FAILURE;
        }

        $deleteFromState = $this->option('delete');

        FetchArcGisFeaturesJob::dispatch($endpoint, $state->id, $deleteFromState);

        $this->info("Queued fetch for {$config['description']} ({$stateAbbr}) (deleteFromState? {$deleteFromState}). FetchArcGisFeaturesJob dispatched.");

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------

    private function listEndpoints(): int
    {
        $endpoints = config('arcgis.endpoints', []);

        if (empty($endpoints)) {
            $this->warn('No endpoints configured in config/arcgis.php.');
            return self::SUCCESS;
        }

        $showAll = (bool) $this->option('all');

        $rows = [];
        foreach ($endpoints as $key => $cfg) {
            /**
             * skip endpoints without
             */
            if (! $showAll && blank($cfg['url'])) {
                continue;
            }
            $rows[] = [
                $key,
                $cfg['slug']       ?? '—',
                $cfg['description'] ?? '—',
                $cfg['url']         ?? '—',
            ];
        }

        $this->table(['Key', 'Slug', 'Description', 'URL'], $rows);

        return self::SUCCESS;
    }
}
