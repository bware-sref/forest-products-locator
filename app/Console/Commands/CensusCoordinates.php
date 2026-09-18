<?php

namespace App\Console\Commands;

use App\Services\CensusGeocoderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('census:coords
    {longitude : x-coordinate on the globe}
    {latitude : y-coordinate on the globe}
')]
#[Description('Performs a coordinate query against the US Census Bureau\'s geocoding API.')]
class CensusCoordinates extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CensusGeocoderService $geo)
    {
        //
        $this->newLine();

        $args = collect($this->arguments())->except('command')->toArray();
        // dump($args);

        /**
         * the arguments are required so we shouldn't need to fail.
         */
        $results = $geo->coordinates(...$args);

        $this->newLine();

        if (empty($results)) {
            $this->error("Empty Results?!?");
            dump($results);
            return parent::FAILURE;
        }

        $this->info("Results for coordinates [{$args['longitude']}, {$args['latitude']}]:\n");
        dump($results);
    }
}
