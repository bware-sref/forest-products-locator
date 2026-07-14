<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMillMillTypes;
use App\Models\Mill;
use App\Models\Scopes\ApprovedScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zed:process-mill-mill-types {mill* : id(s) of Mill(s) for which to process MillTypes}')]
#[Description('Reads mills.type value and creates relationships from the Mill to MillTypes')]
class DispatchProcessMillMillTypes extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = ProcessMillMillTypes::class;

        // get the mill ids
        $millIds = $this->argument('mill');

        $audit = [
            'total' => \count($millIds),
            'success' => 0,
            'failed' => 0,
            'missing' => [],
        ];        

        /**
         * We could search all mill ids up front, but that might not help us identify granular failure as easily...
         */
        foreach ($millIds as $millId) {
            /**
             * Remember to query withoutGlobalScope()!
             * Otherwise, you'll get an empty result.
             */
            $mill = Mill::withoutGlobalScope(ApprovedScope::class)->find($millId, '*');

            /**
             * if we didn't find the mill, output that situation and continue...
             */
            if (!$mill) {
                $this->error("No Mill found with id '{$millId}'. Continuing...");
                $audit['missing'][] = $millId;
                // missing technically lets us skip counting failures...
                $audit['failed']++;                
                continue;
            }

            /**
             * We could also check for empty mills.type here, but if we're doing it in the job as well, then we can just let it
             * handle all the logging and error reporting for that.
             */

            ProcessMillMillTypes::dispatch($mill);
            $this->info("Dispatched {$job} job for Mill #{$millId}.");
            $audit['success']++;
        }

        if ($audit['total'] !== $audit['success']) {
            $this->warn("Only dispatched {$job} jobs for {$audit['success']} Mills instead of {$audit['total']} as expected.");
            $this->warn("Mills with the following ids were not found: " . print_r($audit['missing'], true));
            return parent::FAILURE;
        }

        $this->info("Dispatched {$audit['success']} {$job} jobs for {$audit['total']} Mills.");
        return parent::SUCCESS;
    }
}
