<?php

namespace App\Jobs;

use App\Models\Mill;
use App\Services\GeocodingService;
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
    public function handle(GeocodingService $geo): void
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
         * probably a good idea
         * what exactly are we doing?
         * if we have an address, use geocode
         * if we have latlng, use reverse
         * if we have both, use both and compare the results
         */
        $geocode = $this->mill->hasAddress() ? $geo->geocode($this->mill->rawAddress()) : [];

        // $reverse = $this->mill->hasLatLng() ? $geo->reverse($this->mill->longitude, $this->mill->latitude) : [];
        $reverse = $this->mill->hasLatLng() ? $geo->reverse(...$this->mill->lngLat()) : [];

        if ($geocode != $reverse) {
            Log::warning(self::class.': geocode and reverse geocode returned different data!?!', [
                'geocode' => $geocode,
                'reverse' => $reverse,
            ]);
        }
    }
}
