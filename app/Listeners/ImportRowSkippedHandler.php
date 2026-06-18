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
        Log::debug('ImportRowSkipped before incrementing!', [
            'import_log.id' => $event->import_log->id,
            'updatedAt' => $event->import_log->updated_at->toDateTimeString(),
            'row_data' => $event->row_data ?? 'No row data on skipped row!?!',
            'failedRows' => $event->import_log->failed_rows,
        ]);

        // $event->import_log->failed_rows += 1;
        // $event->import_log->save();
        /**
         * Phucking Intelephense phailing to discern default values...
         */        
        $event->import_log->increment('failed_rows', amount: 1);

        Log::debug('ImportRowSkipped after incrementing!', [
            'import_log.id' => $event->import_log->id,
            'updatedAt' => $event->import_log->updated_at->toDateTimeString(),
            'row_data' => $event->row_data ?? 'No row data on skipped row!?!',
            'failedRows' => $event->import_log->failed_rows,
        ]);
    }
}
