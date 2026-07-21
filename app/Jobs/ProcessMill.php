<?php

namespace App\Jobs;

use App\Models\Import;
use App\Models\Mill;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class ProcessMill implements ShouldQueue
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
         * NOTE: dispatched this way (standalone), this chain isn't tracked by
         * any Bus::batch() — a batch built from ProcessMill instances only
         * "sees" this dispatching job, which finishes as soon as the chain is
         * enqueued, not when the chain actually completes. When you need
         * batch progress/then()/finally() to wait for the real work, pass
         * jobChain() results straight into Bus::batch() instead of wrapping
         * them in ProcessMill (see ProcessImportedMills).
         */
        Bus::chain(self::jobChain($this->mill))->dispatch();
    }

    /**
     * The per-mill processing chain. Public/static so batch-driven callers
     * (ProcessImportedMills) can pass these directly into Bus::batch() as
     * chains-within-a-batch, which — unlike dispatching this job standalone —
     * Laravel tracks as complete only once the last job in the chain runs.
     *
     * $allowFailures controls how each job in the chain responds to its own
     * errors: swallow-and-continue (true) so a batch built to tolerate
     * failures can still finish and dispatch FinalizeMillImport, or truly
     * fail (false) so a strict batch cancels as originally intended — see
     * each job's catch block.
     *
     * @return array<int, ShouldQueue>
     */
    public static function jobChain(Mill $mill, bool $allowFailures = false): array
    {
        return [
            new GeocodeMill($mill, $allowFailures),
            new ProcessMillState($mill, $allowFailures),
            new ProcessMillMillTypes($mill, $allowFailures),
            new ProcessMillWoodSpecies($mill, $allowFailures),
            new UpdateImportProcessedRows($mill, $allowFailures),
        ];
    }
}
