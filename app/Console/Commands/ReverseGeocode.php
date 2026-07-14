<?php

namespace App\Console\Commands;

use App\Services\GeocodingService;
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
    public function handle(GeocodingService $geo)
    {
        if (empty($this->option('lng')) || empty($this->option('lat'))) {
            $this->error('"lng" and "lat" are required parameters. Exiting...');
            return parent::FAILURE;
        }

        $result = $geo->reverse($this->option('lng'), $this->option('lat'), $this->option('limit'));

        dump($result);

        return parent::SUCCESS;
    }
}
