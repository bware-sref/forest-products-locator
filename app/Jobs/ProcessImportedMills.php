<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class ProcessImportedMills implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Import $import
    ) {}
    
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for '.$this->import->id.' was cancelled?');

            return;
        }        

        Log::debug(self::class.': setting up job batch to process '.$this->import->imported_rows.' mills for import #'.$this->import->id.'.');

        // we should make sure we get mills back before tearing out
        $mills = Mill::pending()
            ->where('import_id', $this->import->id)
            ->lazyById();

        if (1 > $mills->count()) {
            Log::error(self::class.' no mills found for import #'. $this->import->id.'?!?');
            return;
        }

        $jobs = [];
        
        foreach ($mills as $mill) {
            $jobs[] = [
                // geocode first so we'll (hopefully) have full address info
                new GeocodeMill($mill),
                new ProcessMill($mill),
            ];
        }

        $batch = Bus::batch($jobs)
            ->before(function (Batch $batch) {
                Log::debug('Before batch #'.$batch->id.'.');
            })->progress(function (Batch $batch) {
                Log::debug('a single job for batch #'.$batch->id.' has completed.');
            })->then(function(Batch $batch) {
                Log::debug(self::class.' finished jobs batch #'.$batch->id.' for import #'.$this->import->id.'.');
            })->catch(function (Batch $batch) {
                Log::error('Catch for batch #'.$batch->id.'.');
            })->finally(function (Batch $batch) {
                Log::debug('Finally for batch #'.$batch->id.'.');
            })->dispatch();
    }
}
