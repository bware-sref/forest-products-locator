<?php

namespace App\Console\Commands;

use App\Services\GeocodingService;
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
    public function handle(GeocodingService $geo)
    {

        if (empty($this->option('query'))) {
            $this->error('The "query" option cannot be empty. Exiting...');
            return parent::FAILURE;
        }

        $result = $geo->geocode($this->option('query'), $this->option('limit'));

        dump($result);

        return parent::SUCCESS;
    }
}
