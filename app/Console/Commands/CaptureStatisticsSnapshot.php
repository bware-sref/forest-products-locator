<?php

namespace App\Console\Commands;

use App\Statistics\Snapshots;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('statistics:snapshot')]
#[Description('Record today’s per-state mill statistics (the history behind the Statistics "over time" charts)')]
class CaptureStatisticsSnapshot extends Command
{
    public function handle(): int
    {
        $states = Snapshots::capture();
        $this->info("Recorded statistics for {$states} states.");

        return self::SUCCESS;
    }
}
