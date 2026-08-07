<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateForestType;
use Illuminate\Database\Seeder;

class StateForestTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateForestType::factory()
                ->count(rand(2, 4))
                ->for($state)
                ->create();
        }
    }
}
