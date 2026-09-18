<?php

namespace App\Jobs;

use App\Enums\PublicationStatus;
use App\Models\Mill;
use App\Services\CensusGeocoderService;
use App\Services\GeocodingService;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeocodeMill implements ShouldQueue
{
    use Queueable, Batchable;

    /**
     * Create a new job instance.
     *
     * $allowFailures: false (default) fails this job for real on error, which
     * aborts the rest of this mill's chain and cancels a strict batch (the
     * spreadsheet-import pipeline's intent). true logs + records the failure
     * and returns normally instead, so a batch built to tolerate failures
     * (ArcGIS imports) keeps going. See ProcessMill::jobChain().
     */
    public function __construct(
        public Mill $mill,
        public bool $allowFailures = false,
        public bool $doCensusLookup = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(GeocodingService $geo, CensusGeocoderService $census): void
    {
        // add null-safe prefix to ->s
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->mill->import_id.' or Mill #'.$this->mill->id.' was cancelled?');

            return;
        }

        try {
            $this->geocode($geo, $census);
        } catch (Throwable $e) {
            $msg = self::class.": failed to geocode Mill #{$this->mill->id}: {$e->getMessage()}";
            Log::error($msg);
            $this->mill->recordProcessingFailure($msg);

            if (! $this->allowFailures) {
                throw $e;
            }
        }
    }

    private function geocode(GeocodingService $geo, CensusGeocoderService $census): void
    {
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
         *
         * Hmm...
         * Having both coordinates and an address doesn't necessarily mean that it doesn't necessarily mean we don't need to do a lookup.
         * For example, if the address has been updated since the last coordinate lookup, then we need a lookup to verify coordinates.
         * But how can we know if the address has been updated since the last lookup?
         * We could add an additional field to indicate that the physical address has changed...
         * Indeed.
         * Okay.
         * That would tell us if we need to do a geocode lookup, but it doesn't establish the action to take when the address
         * in geocoding results differs from the address we were given.
         * Well, maybe we flag these with "needs_review" and update the import publishing job to only publish mills from this import
         * that don't need review?
         * 
         * Also, I keep wanting to invert this conditional to make if (not both) the first case.
         * I don't think that gets us anything but it feels cleaner :shrugs:
         */

        if (! $this->mill->hasAddress() && ! $this->mill->hasLatLng()) {
            $this->invalidateMillAndDie();
        }

        /**
         * If we already have both, this is where we start needing the address_changed indicator.
         * If we have an address but no coordinates, we need coordinates.
         * If we have coordinates but no address, we would benefit from having address, but it's not truly essential.
         * If the address has changed, we need to know so we can update the coordinates.
         */

        /**
         * Initialize, man!
         */
        $results = [];
        $censusResults = [];

        if ($this->mill->hasAddress()) {
            $rawAddress = $this->mill->getRawAddress();
            Log::debug(self::class.":\nabout to do a geocode lookup for mill #{$this->mill->id} in import #{$this->mill->import_id}:\n", [
                "\nrawAddress:\n" => $rawAddress,
            ]);
            $results = $geo->geocode($rawAddress, biasPosition: false);

            if ($this->doCensusLookup) {
                $censusResults = $census->oneLineAddress($rawAddress);
            }
        } else if ($this->mill->hasLatLng()) {
            // Log::debug(self::class.": about to do a REVERSE geocode (edocoeg) lookup for mill #{$this->mill->id} in import #{$this->mill->import_id}: ", [
            //     'mill->lngLat()' => $this->mill->lngLat(),
            // ]);
            $results = $geo->reverse(...$this->mill->lngLat());

            /**
             * the Census Bureau coordinate lookup isn't useful for our purposes.
             */
        }

        // check for empty results?
        if (empty($results)) {
            Log::debug(self::class.": no results for Mill #{$this->mill->id}?!?\nNot sure why this would happen...", [
                "\nmill\n" => $this->mill->toArray()
            ]);
            return;
        }

        /**
         * peel off the outer array
         */
        $results = !empty($results[0]) ? $results[0] : $results;

        Log::debug(self::class.": Mill #{$this->mill->id} geocode results: \n", [
            'results' => $results,
        ]);

        /**
         * this is the array we'll pass to mill->update()
         * Do we want to do any checks against what might already be present, or just overwrite?
         *
         * Haha!
         * We should probably verify that the two addresses are not wildly different.
         * Also, we should check to see if the mill was sourced from ArcGIS or a spreadsheet before overwriting
         * the address or coordinates.
         * It can be valid to overwrite those, but only if it's a subsequent edit.
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

        if (!empty($censusResults)) {
            $censusUpdates = [
                'physical_address' => $censusResults['street_address'] ?? $this->mill->physical_address,
                'physical_city' => $censusResults['city'] ?? $this->mill->physical_city,
                // census lookups don't include county, but include it anyway so it won't always get flagged in the diff
                'county_name' => $censusResults['county'] ?? $this->mill->county_name,
                'physical_zip' => $censusResults['zip'] ?? $this->mill->physical_zip,
                'latitude' => $censusResults['latitude'] ?? $this->mill->latitude,
                'longitude' => $censusResults['longitude'] ?? $this->mill->longitude,
            ];

            $censusDiff = array_diff_assoc($censusUpdates, $original);

            Log::debug("\n".self::class."::geocode():\n", [
                "\ncensusDiff:\n" => $censusDiff,
            ]);

            /**
             * updateDiff?
             */
            $updateDiff = array_diff_assoc($updates, $censusUpdates);
            Log::debug("\n".self::class."::geocode():\n", [
                "\nupdateDiff:\n" => $updateDiff,
            ]);

            /**
             * @TODO make some decision
             */
        }

        if (empty($diff)) {
            Log::debug(self::class.": no updates for Mill #{$this->mill->id}?!?");
            return;
        }

        Log::debug(self::class.": preparing updates for Mill #{$this->mill->id}:\n", [
            "\noriginal\n" => $original,
            "\nupdates\n" => $updates,
            "\ndiff:\n" => $diff,
        ]);

        $this->mill->update($updates);

        return;
    }

    protected function invalidateMillAndDie()
    {
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

        throw new \RuntimeException($msg);
    }

    protected function shouldGeocode(): bool
    {
        /**
         * shouldGeocode() isn't really the question we're trying to answer as much as "shouldOverwrite address and/or coordinates?"
         * That's a different question.
         * 
         * If the mill is pending, it's almost certainly from an ArcGIS or spreadsheet import.
         */
        if ($this->mill->isPending()) {

            /**
             * Does the source even matter?
             * If it's pending and hasAddress and has coordinates, don't geocode.
             */
            /**
             * If this is a pending ArcGIS mill, we only need to geocode if it does not have an address (looking at you North Carolina)
             */
            if ($this->mill->isArcGis() && $this->mill->hasAddress()) {
                return false;
            }

            /**
             * If it's a spreadsheet mill that doesn't have coordinates
             * Or any mill without coordinates?
             */
            if ($this->mill->isSpreadsheet() && $this->mill->hasLatLng()) {
                return false;
            }
        }
        return true;
    }
}
