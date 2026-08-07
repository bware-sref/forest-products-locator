<?php

namespace Database\Seeders;

use App\Models\State;
use App\Models\StateAssistanceCategory;
use App\Models\StateAssistanceLink;
use Illuminate\Database\Seeder;

class StateAssistanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Links are seeded here alongside their categories (see StateAssistanceLinkSeeder).
     */
    public function run(): void
    {
        foreach (State::whereIn('abbreviation', State::$southernStates)->get() as $state) {
            StateAssistanceCategory::factory()
                ->count(rand(2, 4))
                ->for($state)
                ->has(StateAssistanceLink::factory()->count(rand(2, 5)), 'links')
                ->create();
        }
    }
}
