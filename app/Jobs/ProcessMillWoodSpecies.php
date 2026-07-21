<?php

namespace App\Jobs;

use App\Models\Mill;
use App\Models\WoodSpecies;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class ProcessMillWoodSpecies implements ShouldQueue
{
    use Batchable, Queueable;

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

        try {
            $this->assignWoodSpecies();
        } catch (Throwable $e) {
            $msg = self::class.": failed to process Mill #{$this->mill->id}: {$e->getMessage()}";
            Log::error($msg);
            $this->mill->recordProcessingFailure($msg);

            if (! $this->allowFailures) {
                throw $e;
            }
        }
    }

    private function assignWoodSpecies(): void
    {
        /**
         * fetch the nearly raw imported value (nearly because it's been exploded on either | or ,)
         */
        $woodSpecies = $this->mill->getRawSpeciesList();

        /**
         * Bail if this mill has an empty species field.
         */
        if (empty($woodSpecies)) {
            // Log::debug(self::class.": Mill #{$this->mill->id} has an empty `species` field! Nothing for us to do here.", [
            //     'mill' => collect($this->mill->toArray())->only(['name', 'species'])->all(),
            // ]);
            return;
        }

        $woodSpeciesCount = \count($woodSpecies);

        /**
         * Get all WoodSpecies from DB, names keyed by id so we can easily convert them all to lowercase for comparison.
         * Or, should we make the query whereIn?
         * Either way, we have to identify which values are not already present in the DB and then decide whether to add them.
         * I think we should probably add them since they're coming from state data.
         * However, if some rando submits new values, then we should be more thoughtful.
         * So, back to this, let's get all the names keyed by id so we can squish them to lower case.
         */
        $allWoodSpecies = WoodSpecies::all()
            ->pluck('name', 'id')
            ->map(fn ($item) => Str::lower($item))
            ->toArray();

        /**
         * Collect all WoodSpecies ids so we can attach them in a single DB query.
         */
        $speciesIds = [];

        /**
         * Loop over the values found in the imported data
         */
        foreach ($woodSpecies as $wSpecies) {
            /**
             * I almost forgot!
             * Be sure to convert $wSpecies to lower case inline without storing the result so we don't corrupt the original value (yet*).
             * (*prolly gonna do ucwords on $wSpecies before inserting)
             */
            $id = array_search(Str::lower($wSpecies), $allWoodSpecies);

            if (false === $id) {
                Log::warning(self::class.": Mill #{$this->mill->id} has an unknown WoodSpecies value: `{$wSpecies}`. Preparing to insert...", [
                    'allWoodSpecies' => $allWoodSpecies,
                ]);

                $newWood = WoodSpecies::create([
                    'name' => Str::ucwords($wSpecies),
                ]);
                $id = $newWood->id;
                /**
                 * Do we even need to add this to the allWoodSpecies array?
                 * Because the next job will do a fresh fetch...
                 */
                $allWoodSpecies[$id] = $newWood->name; // so we get the ucwords version
            }

            /**
             * Now that we're keying the names by id, we should already have the id from array_search().
             * Or else we just created it.
             * And we really don't need to key the speciesIds by name, but oh well.
             */
            $speciesIds[$wSpecies] = $id;
        }

        if (empty($speciesIds)) {
            Log::error(self::class.": no valid WoodSpecies found for Mill #{$this->mill->id}. Instead we found the following unknown WoodSpecies: ", [
                'unknownWoodSpecies' => $woodSpecies,
            ]);
            return;
        }

        $foundWoodSpeciesCount = \count($speciesIds);
        // Log::debug(self::class.": for Mill #{$this->mill->id}, found {$foundWoodSpeciesCount} valid WoodSpecies out of {$woodSpeciesCount} in the raw data: ", [
        //     'foundSpeciesIds' => $speciesIds,
        //     'woodSpecies' => $woodSpecies,
        // ]);

        /**
         * @FYI: we have to manually supply the timestamp values for the pivot table; non-pivot seems to do it automatically.
         */
        $now = now();
        $extra = ['created_at' => $now, 'updated_at' => $now];

        /**
         * We need to use sync() (or maybe syncWithPivotValues()) because attach() will create duplicates.
         * Yep, just confirmed that sync doesn't update timestamps.
         * syncWithPivotValues() it is!
         */
        // $this->mill->woodSpecies()->attach($speciesIds, $extra);
        $this->mill->woodSpecies()->syncWithPivotValues($speciesIds, $extra);

        //
        // Log::debug(self::class." attached {$foundWoodSpeciesCount} WoodSpecies to Mill #{$this->mill->id}.", [
        //     'foundWoodSpecies' => $speciesIds,
        // ]);

        return;

        //
    }
}
