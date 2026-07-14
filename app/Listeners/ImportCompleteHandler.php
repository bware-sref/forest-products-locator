<?php

namespace App\Listeners;

use App\Jobs\ProcessImportedMills;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportCompleteEvent;

class ImportCompleteHandler
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ImportCompleteEvent $event): void
    {
        /**
         * Refresh the log before spitting out mess.
         */
        $log = $event->import_log;
        $log->refresh();

        Log::debug('ImportCompleted!', [
            // 'event' => $event
            'logId' => $event->import_log->id,
            'importedFile' => $event->import_log->original_file_name,
            'totalRows' => $event->import_log->total_rows,
            'importedRows' => $event->import_log->imported_rows,
            'failedRows' => $event->import_log->failed_rows,
            'startedAt' => $event->import_log->started_at,
            'updatedAt' => $event->import_log->updated_at,
            'completedAt' => $event->import_log->completed_at,
            'stateId' => $event->import_log->state_id,
            'deleteFromState' => $event->import_log->delete_from_state,
        ]);

        /**
         * If the log has both start and end time 
         * and the total number of rows is greater than 0
         * and the number of imported rows is also greater than 0
         */
        if (!empty($log->started_at) && 
            !empty($log->completed_at) && 
            0 < $log->imported_rows &&
            $log->imported_rows <= $log->total_rows
        ) {
            ProcessImportedMills::dispatch($log);
            Log::debug("Dispatched ProcessImportedMills job for import #{$log->id} to process {$log->imported_rows} out of {$log->total_rows} total rows!");
        }
    }
}
