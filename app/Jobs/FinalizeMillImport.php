<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\Import;
use App\Models\Mill;
use App\Models\Scopes\ApprovedScope;
use App\Models\User;
use App\Support\ActivitySource;
use Illuminate\Bus\Batch;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalizeMillImport implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $importId,
        // stateId might be null
        public ?int $stateId = null,
        // deleteFromState shouldn't ever be null, but just in case
        public ?bool $deleteFromState = false,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $stateId = $this->stateId ?? 0; // 0 means stateId was null, since we can't use null in a string.
        Log::debug(self::class.": preparing to finalize Import #{$this->importId} for State #{$stateId}.");
        /**
         * What do we need to do?
         *  - Start a transaction
         * if stateId is not null, we need to delete other mills from this state
         *  - delete mills where
         *      - mill.state_id = imports.state_id
         *      - AND
         *      - mill.import_id IS NOT NULL
         *      - AND
         *      - mill.import_id !== import.id
         *  - update mills to set status to approved where mills.import_id === import.id
         */
        DB::transaction(function () {
            $removed = [];
            if (null !== $this->stateId && $this->deleteFromState) {
                // kept for the activity log: the delete is one bulk query, so the mills aren't logged one by one
                $removed = Mill::oldImports($this->importId, $this->stateId)->pluck('mill_name', 'id')->all();
                $howManyDeletes = Mill::deleteOldImports($this->importId, $this->stateId);

                Log::debug(self::class.": deleted {$howManyDeletes} Mills from State #{$this->stateId} for Import #{$this->importId}.");
            }
            /**
             * Now publish all of the Mills from this import
             */
            $didItWork = Mill::publishFromImport($this->importId);

            Log::debug(self::class.": did publishing Mills from Import #{$this->importId} work?", [
                'didItWork' => (string) $didItWork,
            ]);

            $import = Import::find($this->importId);
            $import?->update(['status' => ImportStatus::Completed]);

            $this->logSummary($import, $removed);
        });
        Log::debug(self::class.": finalized Import #{$this->importId} for State #{$stateId}.");
    }

    /**
     * One activity log entry per import. Mills aren't logged one by one while an import works on them
     * (Mill::skipsAudit()), and both publishing and removing them are bulk queries.
     *
     * @param  array<int, string>  $removed  id => name of the mills this import replaced
     */
    protected function logSummary(?Import $import, array $removed): void
    {
        $published = Mill::withoutGlobalScope(ApprovedScope::class)->where('import_id', $this->importId)->count();

        $logger = activity()->useLog(ActivitySource::IMPORT);
        if ($import) {
            $logger->performedOn($import)->causedBy($import->user_id ? User::find($import->user_id) : null);
        }

        $logger
            ->event('finalized')
            ->withProperties([
                'import_id' => $this->importId,
                'state_id' => $this->stateId,
                'published' => $published,
                'removed' => count($removed),
                'removed_mills' => $removed,
            ])
            ->log("Import #{$this->importId} published {$published} mills".($removed ? ' and removed '.count($removed) : ''));
    }
}
