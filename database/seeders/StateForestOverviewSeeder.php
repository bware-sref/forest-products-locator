<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateForestOverview;
use Illuminate\Database\Seeder;

class StateForestOverviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateForestOverview::factory()->for($state)->create();
        }
    }
}
