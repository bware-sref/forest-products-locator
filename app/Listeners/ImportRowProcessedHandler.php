<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowProcessedEvent as ImportRowProcessed;

class ImportRowProcessedHandler
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
    public function handle(ImportRowProcessed $event): void
    {
        /**
         * Use refresh() to make sure the log is...fresh.
         * Use refresh() because fresh() returns a separate instance which must be assigned to a variable.
         */
        $event->import_log->refresh();
        
        // Log::debug('ImportRowProcessed after refreshing the log but before incrementing processed_rows: ', [
        //     'importId' => $event->import_log->id,
        //     'entryId' => $event->entry->id,
        //     'staleProcessedRows' => $staleValue,
        //     'processRows' => $event->import_log->processed_rows,
        //     'updatedAt' => $event->import_log->updated_at->toDateTimeString(),
        // ]);

        /**
         * Phucking Intelephense phailing to discern default values...
         */
        $event->import_log->increment('processed_rows', amount: 1);


        // Log::debug('ImportRowProcessedHandler: ', [
        //     'importId' => $event->import_log->id,
        //     // 'entry' => $event->entry ?? 'no entry on event?!?',
        //     'entryId' => $event->entry->id,
        //     'millName' => $event->entry->mill_name,
        //     // 'row_data' => $event->row_data ?? 'no row_data on event?!?',
        //     'processedRows' => $event->import_log->processed_rows,
        //     'updatedAt' => $event->import_log->updated_at->toDateTimeString(),
        // ]);
    }
}
