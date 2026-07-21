<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class UpdateImportProcessedRows implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * Create a new job instance.
     *
     * $allowFailures: false (default) fails this job for real on error, which
     * cancels a strict batch (the spreadsheet-import pipeline's intent).
     * true logs + records the failure and returns normally instead, so a
     * batch built to tolerate failures (ArcGIS imports) keeps going. See
     * ProcessMill::jobChain(). This is the last job in the chain, so unlike
     * the others, failing here doesn't orphan any further jobs — it's here
     * for consistency and so a failure here still increments failed_rows.
     */
    public function __construct(
        public Mill $mill,
        public bool $allowFailures = false,
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

        try {
            $this->mill->import->increment('processed_rows');
        } catch (Throwable $e) {
            $msg = self::class.": failed to update processed_rows for Mill #{$this->mill->id}: {$e->getMessage()}";
            Log::error($msg);
            $this->mill->recordProcessingFailure($msg);

            if (! $this->allowFailures) {
                throw $e;
            }
        }
    }
}
