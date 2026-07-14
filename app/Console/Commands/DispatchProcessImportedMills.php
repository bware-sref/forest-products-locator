<?php

namespace App\Console\Commands;

use App\Models\Import;
use App\Jobs\ProcessImportedMills;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('zed:process-imported-mills {import : Id of the import to process}')]
#[Description('Dispatches a ProcessImportedMills job for the specified Import')]
class DispatchProcessImportedMills extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {        
        $importId = $this->argument('import');
        $job = ProcessImportedMills::class;

        // fetch the import
        // phucking Intelephense
        $import = Import::find($importId, '*');

        if (! $import) {
            $msg = "Unable to dispatch {$job} job for Import #{$importId} because Import #{$importId} was not found.";
            Log::error($msg);
            $this->error($msg);

            return parent::FAILURE;
        }

        ProcessImportedMills::dispatch($import);

        $this->info("Dispatched {$job} for Import #{$importId}.");
        return parent::SUCCESS;
    }
}
