<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use RedSquirrelStudio\LaravelBackpackImportOperation\Events\ImportStartedEvent as ImportStarted;

class ImportStartedHandler
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
    public function handle(ImportStarted $event): void
    {
        Log::debug('ImportStarted!', [
            // 'event' => $event
            'importId' => $event->import_log->id,
            'originalFile' => $event->import_log->original_file_name,
            'file' => $event->import_log->file_path,
        ]);
    }
}
