<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateEconomicImpact;
use Illuminate\Database\Seeder;

class StateEconomicImpactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateEconomicImpact::factory()->for($state)->create();
        }
    }
}
