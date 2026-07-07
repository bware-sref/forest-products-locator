<?php

namespace App\Jobs;

use App\Models\Mill;
use App\Models\MillType;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMillMillTypes implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mill $mill
    )
    {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // add null-safe prefix to ->s
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->mill->import_id.' or Mill #'.$this->mill->id.' was cancelled?');

            return;
        }

        /**
         * Extract values from this Mill's `type` field.
         */
        $millTypes = $this->mill->getRawTypeList();

        /**
         * Bail if this mill has an empty type field.
         */
        if (empty($millTypes)) {
            Log::debug(self::class.": Mill #{$this->mill->id} has an empty `type` field! Nothing for us to do here.", ['mill' => $this->mill->toArray()]);
            return;
        }

        $millTypeCount = \count($millTypes);

        /**
         * Get all MillType ids from DB, keyed by name
         * We could actually just do whereIn('name', $millTypes)...
         * And FFS, I apparently already wrote a method to handle most of this...
         * MillType::rawToIds(string $raw)
         * It even explodes the string, but it didn't trim the resulting values...until now!
         * It also logs a warning if the count of the raw list and actual DB results differ.
         */
        $allMillTypes = MillType::all()->pluck('id', 'name')->toArray();

        /**
         * Collect all MillType ids so we can attach them in a single DB query.
         */
        $typeIds = [];

        foreach ($millTypes as $mType) {
            if (! \array_key_exists($mType, $allMillTypes)) {
                Log::warning(self::class.": Mill #{$this->mill->id} has an unknown MillType value: `{$mType}`.");
                /**
                 * Question for the future: should we insert new values?
                 * If we do, we'll need to update $allMillTypes afterward, which is fine.
                 * In fact, if we were crazier, we might trigger a job to get insert new MillTypes and requeue this job to run after
                 * that one...
                 */
                continue;
            }

            // key ids by name
            $typeIds[$mType] = $allMillTypes[$mType];
        }

        if (empty($typeIds)) {
            Log::error(self::class.": no valid MillTypes found for Mill #{$this->mill->id}. Instead we found the following unknown MillTypes: ", [
                'unknownMillTypes' => $millTypes,
            ]);
            return;
        }

        $foundMillTypeCount = \count($typeIds);
        Log::debug(self::class.": for Mill #{$this->mill->id}, found {$foundMillTypeCount} valid MillTypes out of {$millTypeCount} in the raw data: ", [
            'foundTypeIds' => $typeIds,
            'millTypes' => $millTypes,
        ]);

        $now = now();
        $extra = ['created_at' => $now, 'updated_at' => $now];

        /**
         * Doh!
         * We need to use sync() instead because attach doesn't check existing
         * Actually, we probably need syncWithPivotValues() so we can add timestamps.
         * Yep, just confirmed that sync doesn't update timestamps.
         * syncWithPivotValues() it is!
         * 
         * Also, I discovered that sync() doesn't necessarily eliminate existing rows with duplicate ids.
         * If the new ids you pass are all already in the DB and duplicated, sync() doesn't insert new ones.
         * However, because sync() also only deletes ids that aren't in the new list, it doesn't touch the existing duplicates.
         * It all makes sense, but the docs didn't cover that particular weird scenario.
         */
        $this->mill->millTypes()->syncWithPivotValues($typeIds, $extra);

        //
        Log::debug(self::class." attached {$foundMillTypeCount} MillTypes to Mill #{$this->mill->id}.");

        return;
    }
}
