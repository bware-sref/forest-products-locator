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

        $this->mill->millTypes()->attach($typeIds, $extra);

        //
        Log::debug(self::class." attached {$foundMillTypeCount} MillTypes to Mill #{$this->mill->id}.");

        return;
    }
}
