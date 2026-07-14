<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportRowSkippedEvent as ImportRowSkipped;

class ImportRowSkippedHandler
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
    public function handle(ImportRowSkipped $event): void
    {
        /**
         * Freshen the import_log record.
         * Use refresh() because fresh() returns a separate instance which must be assigned to a variable.
         */
        $event->import_log->refresh();

        /**
         * Phucking Intelephense phailing to discern default values...
         */        
        $event->import_log->increment('failed_rows', amount: 1);

        Log::debug('ImportRowSkipped after incrementing!', [
            'import_log.id' => $event->import_log->id,
            'row_data' => $event->row_data ?? 'No row data on skipped row!?!',
            'failedRows' => $event->import_log->failed_rows,
            'updatedAt' => $event->import_log->updated_at->toDateTimeString(),
        ]);
    }
}
