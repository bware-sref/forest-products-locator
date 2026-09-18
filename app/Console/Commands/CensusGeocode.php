<?php

namespace App\Console\Commands;

use App\Services\CensusGeocoderService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('census:geocode
    {--query= : address to search}
')]
#[Description('Performs a onelineaddress geocode lookup against the US Census Bureau\'s geocoding API.')]
class CensusGeocode extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CensusGeocoderService $geo)
    {
        $this->newLine();

        if (empty($this->option('query'))) {
            $this->error('The "query" option cannot be empty. Exiting...');
            return parent::FAILURE;
        }        

        $query = $this->option('query');
        // maybe add benchmark option later
        $result = $geo->oneLineAddress($query);

        if (empty($result)) {
            $this->error("Empty result?!?");
            dump($result);
            return parent::FAILURE;
        }

        $this->newLine();
        $this->info("Geocoding result for query '{$query}':\n");
        dump($result);

        return parent::SUCCESS;
    }
}
