<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StatePage;
use Illuminate\Database\Seeder;

class StatePageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StatePage::factory()->for($state)->create();
        }
    }
}
