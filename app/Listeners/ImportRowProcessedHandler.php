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
        Log::debug('ImportRowProcessed!', [
            'entry' => $event->entry ?? 'no entry on event?!?',
            'row_data' => $event->row_data ?? 'no row_data on event?!?',
        ]);
    }
}
