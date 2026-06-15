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
        Log::debug('ImportRowSkipped!', [
            'row_data' => $event->row_data ?? 'No row data on skipped row!?!',
        ]);
    }
}
