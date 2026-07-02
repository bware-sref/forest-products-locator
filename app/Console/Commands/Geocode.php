<?php

namespace App\Console\Commands;

use Aws\GeoPlaces\GeoPlacesClient;
use Aws\Laravel\AwsFacade as AWS;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('zed:geocode {--query=} {--limit=1}')]
#[Description('Execute a geocode lookup for the given query text with an optional limit.')]
class Geocode extends Command implements PromptsForMissingInput
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        /**
         * @var GeoPlacesClient
         */
        $geoplacesClient = AWS::createClient('geoPlaces');        

        $query = [
            'QueryText' => $this->option('query'),
            'MaxResults' => $this->option('limit'),
        ];

        $result = $geoplacesClient->geocode($query);

        dump($result);

        return parent::SUCCESS;
    }
}
