<?php

namespace App\Jobs;

use App\Models\Mill;
use Aws\GeoPlaces\GeoPlacesClient;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GeocodeMill implements ShouldQueue
{
    use Queueable, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mill $mill
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeoPlacesClient $client): void
    {
        if ($this->batch()->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for '.$this->mill->import_id.' was cancelled?');

            return;
        }
        /**
         * What all needs to happen?
         * 
         * AWS Geocode lookup!
         * if the mill has a physical address field but no lat & long
         *      if physical address (or mailing address) doesn't include the city state and zip, add them to the query
         * else if lat & long but no physical or mailing address
         *      reverse geocode
         */
        Log::debug(self::class.': about to do a lookup for mill #'.$this->mill->id.' in import #'.$this->mill->import_id.'.');

        /**
         * maybe we extract some of this to a model method?
         */
        if (!empty($this->mill->physical_address)) {
        }

        $stuff = $client->reverseGeocode([
            // remember, position is x,y so longitude comes first
            'QueryPosition' => [(float) $this->mill->longitude, (float) $this->mill->latitude]
        ]);
    }
}
