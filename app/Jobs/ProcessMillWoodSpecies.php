<?php

namespace App\Jobs;

use App\Models\Mill;
use App\Models\WoodSpecies;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ProcessMillWoodSpecies implements ShouldQueue
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

        $woodSpecies = Mill::getRawSpeciesList();

        /**
         * Bail if this mill has an empty species field.
         */
        if (empty($woodSpecies)) {
            Log::debug(self::class.": Mill #{$this->mill->id} has an empty `species` field! Nothing for us to do here.", ['mill' => $this->mill->toArray()]);
            return;
        }

        $woodSpeciesCount = \count($woodSpecies);

        /**
         * Get all WoodSpecies ids from DB, keyed by name
         */
        $allWoodSpecies = WoodSpecies::all()->pluck('id', 'name')->toArray();


        /**
         * Collect all WoodSpecies ids so we can attach them in a single DB query.
         */
        $speciesIds = [];

        foreach ($woodSpecies as $wSpecies) {
            if (! \array_key_exists($wSpecies, $allWoodSpecies)) {
                Log::warning(self::class.": Mill #{$this->mill->id} has an unknown WoodSpecies value: `{$wSpecies}`.");
                /**
                 * Question for the future: should we insert new values?
                 * If we do, we'll need to update $allwoodSpecies afterward, which is fine.
                 * In fact, if we were crazier, we might trigger a job to get insert new woodSpecies and requeue this job to run after
                 * that one...
                 */
                continue;
            }

            // key ids by name
            $speciesIds[$wSpecies] = $allWoodSpecies[$wSpecies];
        }

        if (empty($speciesIds)) {
            Log::error(self::class.": no valid WoodSpecies found for Mill #{$this->mill->id}. Instead we found the following unknown WoodSpecies: ", [
                'unknownWoodSpecies' => $woodSpecies,
            ]);
            return;
        }

        $foundWoodSpeciesCount = \count($speciesIds);
        Log::debug(self::class.": for Mill #{$this->mill->id}, found {$foundWoodSpeciesCount} valid WoodSpecies out of {$woodSpeciesCount} in the raw data: ", [
            'foundSpeciesIds' => $speciesIds,
            'woodSpecies' => $woodSpecies,
        ]);

        $now = now();
        $extra = ['created_at' => $now, 'updated_at' => $now];

        $this->mill->woodSpecies()->attach($speciesIds, $extra);

        //
        Log::debug(self::class." attached {$foundWoodSpeciesCount} WoodSpecies to Mill #{$this->mill->id}.");

        return;

        //
    }
}
