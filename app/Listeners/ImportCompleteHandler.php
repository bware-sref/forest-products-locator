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
        Log::debug('ImportCompleted!', [
            // 'event' => $event
            'importedFile' => $event->import_log->original_file_name,
            'totalRows' => $event->import_log->total_rows,
            'processedRows' => $event->import_log->processed_rows,
            'failedRows' => $event->import_log->failed_rows,
        ]);
    }
}
