<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateContact;
use Illuminate\Database\Seeder;

class StateContactSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateContact::factory()
                ->count(rand(1, 3))
                ->for($state)
                ->create();
        }
    }
}
