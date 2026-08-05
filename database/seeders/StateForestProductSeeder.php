<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateForestProduct;
use Illuminate\Database\Seeder;

class StateForestProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateForestProduct::factory()
                ->count(rand(3, 6))
                ->for($state)
                ->create();
        }
    }
}
