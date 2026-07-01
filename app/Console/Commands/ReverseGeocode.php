<?php

namespace App\Console\Commands;

use Aws\GeoPlaces\GeoPlacesClient;
use Aws\Laravel\AwsFacade as AWS;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;

#[Signature('zed:reverse-geocode {--lng=} {--lat=} {--limit=1}')]
#[Description('Given latitude and longitude, performs a reverse geocode lookup')]
class ReverseGeocode extends Command implements PromptsForMissingInput
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $geoPlacesClient = AWS::createClient('geoPlaces');
        //
        $query = [
            'QueryPosition' => [
                (float) $this->option('lng'),
                (float) $this->option('lat')
            ],
            'MaxResults' => $this->option('limit'),
        ];

        $result = $geoPlacesClient->reverseGeocode($query);

        dump($result);

        return parent::SUCCESS;
    }
}
