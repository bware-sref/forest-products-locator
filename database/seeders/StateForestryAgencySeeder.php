<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateForestryAgency;
use Illuminate\Database\Seeder;

class StateForestryAgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateForestryAgency::factory()
                ->for($state)
                ->create([
                    'headline' => $state->name.' Forestry Commission',
                    'cta_1_label' => 'Visit the '.$state->name.' Forestry Commission',
                ]);
        }
    }
}
