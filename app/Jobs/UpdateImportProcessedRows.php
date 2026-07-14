<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateImportProcessedRows implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Mill $mill
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this?->batch()?->cancelled()) {
            // The batch has been cancelled...
            Log::debug(self::class.': Apparently the job batch for Import #'.$this->mill->import_id.' or Mill #'.$this->mill->id.' was cancelled?');

            return;
        }

        /**
         * If this mill isn't part of an import, we don't have anything to do here.
         */
        if (empty($this->mill->import_id)) {
            Log::warning(self::class.": Mill #{$this->mill->id} does not belong to an import. Exiting...");
            return;
        }

        Log::debug(self::class.": updating Import #{$this->mill->import_id} to reflect that Mill #{$this->mill->id} has been processed.");
        /**
         * I wonder if we need to refresh the import first?
         */
        $oldValue = $this->mill->import->processed_rows;
        $this->mill->import->increment('processed_rows');
        $newValue = $this->mill->import->processed_rows;

        Log::debug(self::class.": incremented Import #{$this->mill->import_id}.processed_rows from {$oldValue} to {$newValue}");
    }
}
