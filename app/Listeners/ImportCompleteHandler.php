<?php

namespace App\Listeners;

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

        if (!empty($log->started_at) && !empty($log->completed_at)) {
            Log::debug('We need to queue a job for processing import #' . $log->id . '!');
        }
    }
}
