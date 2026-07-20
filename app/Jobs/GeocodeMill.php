<?php

namespace App\Jobs;

use App\Enums\PublicationStatus;
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
        // add null-safe prefix to ->s
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->mill->import_id.' or Mill #'.$this->mill->id.' was cancelled?');

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
        // Log::debug(self::class.': about to do a lookup for mill #'.$this->mill->id.' in import #'.$this->mill->import_id.'.', [
        //     'rawAddress' => $this->mill->getRawAddress(),
        //     'lngLat' => $this->mill->lngLat(),
        //     'mill' => collect($this->mill->toArray())->only(['latitude', 'longitude', 'physical_address', 'raw_physical_address'])->all(),
        //     'hasAddress?' => $this->mill->hasAddress(),
        //     // 'MILL' => $this->mill->toArray(),
        // ]);

        /**
         * maybe we extract some of this to a model method?
         * probably a good idea
         * what exactly are we doing?
         * if we have an address, use geocode
         * if we have latlng (and no address?), use reverse
         * if we have both, use both and compare the results...actually, let's not.
         */

        if ($this->mill->hasAddress()) {
            // Log::debug(self::class.": about to do a geocode lookup for mill #{$this->mill->id} in import #{$this->mill->import_id}: ", [
            //     'rawAddress' => $this->mill->getRawAddress(),
            // ]);
            $results = $geo->geocode($this->mill->getRawAddress());
        } else if ($this->mill->hasLatLng()) {
            Log::debug(self::class.": about to do a REVERSE geocode (edocoeg) lookup for mill #{$this->mill->id} in import #{$this->mill->import_id}: ", [
                'mill->lngLat()' => $this->mill->lngLat(),
            ]);
            $results = $geo->reverse(...$this->mill->lngLat());
        } else {
            /**
             * Error or invalid?
             * One of them.
             * Either way we move on.
             */
            $msg = "Unable to geocode Mill #{$this->mill->id} because it has neither an address nor a latitude & longitude.";
            Log::error(self::class.": {$msg}: ", [
                'rawPhysicalAddress' => $this->mill->getRawAddress('physical'),
                'rawMailingAddress' => $this->mill->getRawAddress('mailing'),
                'lngLat' => $this->mill->lngLat(),
            ]);

            /**
             * Mark the mill as invalid...
             * ...even though Intelephense is insisting update() has too many arguments.
             */
            $this->mill->update([
                'status' => PublicationStatus::Invalid,
            ]);
            $this->mill->import?->increment('failed_rows');
            $this->fail($msg);
            return;
        }

        /**
         * peel off the outer array
         */
        $results = !empty($results[0]) ? $results[0] : $results;

        // Log::debug(self::class.": Mill #{$this->mill->id} geocode results: ", [
        //     'results' => $results,
        // ]);

        /**
         * this is the array we'll pass to mill->update()
         * Do we want to do any checks against what might already be present, or just overwrite?
         */
        $updates = [
            'physical_address' => $results['street_address'] ?? $this->mill->physical_address,
            'physical_city' => $results['city'] ?? $this->mill->physical_city,
            'county_name' => $results['county'] ?? $this->mill->county_name,
            'physical_zip' => $results['zip'] ?? $this->mill->physical_zip,
            'latitude' => $results['latitude'] ?? $this->mill->latitude,
            'longitude' => $results['longitude'] ?? $this->mill->longitude,
        ];

        $original = collect($this->mill->toArray())->only(array_keys($updates))->toArray();

        $diff = array_diff_assoc($updates, $original);

        if (empty($diff)) {
            Log::debug(self::class.": no updates for Mill #{$this->mill->id}?!?");
            return;
        }

        // Log::debug(self::class.": preparing updates for Mill #{$this->mill->id}: ", [
        //     'original' => $original,
        //     'updates' => $updates,
        //     'diff' => $diff,
        // ]);


        $this->mill->update($updates);

        return;

        // $geocode = $this->mill->hasAddress() ? $geo->geocode($this->mill->getRawAddress()) : [];

        // // $reverse = $this->mill->hasLatLng() ? $geo->reverse($this->mill->longitude, $this->mill->latitude) : [];
        // $reverse = $this->mill->hasLatLng() ? $geo->reverse(...$this->mill->lngLat()) : [];

        // /**
        //  * Of course this happened with the first mill tried.
        //  * For now let's go with the simpler version: 
        //  *      - if hasAddress, do geocode
        //  *      - if no address but has latLng, do reverse
        //  *      - what if neither? flag the record? how?
        //  *          - add another PublicationStatus for errors?
        //  *          - PublicationStatus::Invalid for errors!?!
        //  *          - Or does Errors make more sense?
        //  *          - Yes, Errors makes more sense than Invalid (which could be for a lot of other reasons)
        //  *          - Might also make sense to create an ImportStatus enum...
        //  *      
        //  */
        // if ($geocode != $reverse) {
        //     $diff = array_diff_assoc($geocode, $reverse);
        //     Log::warning(self::class.': geocode and reverse geocode returned different data!?!', [
        //         'geocode' => $geocode,
        //         'reverse' => $reverse,
        //         'diff' => $diff,
        //     ]);
        //     // dump('geo', $geocode, 'reverse', $reverse);
        // }
    }
}
