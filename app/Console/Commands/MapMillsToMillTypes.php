<?php

namespace App\Console\Commands;

use App\Models\Mill;
use App\Models\MillType;
use Illuminate\Console\Command;

class MapMillsToMillTypes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:mill-types';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and MillTypes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
