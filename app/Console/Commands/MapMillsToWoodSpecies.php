<?php

namespace App\Console\Commands;

use App\Models\Mill;
use App\Models\WoodSpecies;
use Illuminate\Console\Command;

class MapMillsToWoodSpecies extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'zed:mill-species';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create database relationships between Mills and WoodSpecies';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
    }
}
