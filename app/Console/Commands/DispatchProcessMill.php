<?php

namespace App\Console\Commands;

use App\Jobs\ProcessMill;
use App\Models\Mill;
use App\Models\Scopes\ApprovedScope;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('zed:process-mill {mill* : Id(s) of the Mill(s) to process}')]
#[Description('Dispatches ProcessMill job(s) for the Mill(s) specified by id(s)')]
class DispatchProcessMill extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $job = ProcessMill::class;
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
            if (!$mill) {
                $this->error("No Mill found with id '{$millId}'. Continuing...");
                $audit['missing'][] = $millId;
                // missing technically lets us skip counting failures...
                $audit['failed']++;                
                continue;
            }

            ProcessMill::dispatch($mill);
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
