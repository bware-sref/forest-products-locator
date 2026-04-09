<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Ideally we'll end up with at least one agent for each southern state.
         * And realisitcally, each state will have multiple agents.
         * But that sort of begs the question of MillEdits and new Mill submissions getting assigned to the correct agent. So for now, we'll just create one agent for each state.
         */
        $stateIds = State::whereIn('abbreviation', State::$southernStates)->pluck('id')->toArray();
        foreach ($stateIds as $stateId) {
            /**
             * Should we create a random number of agents for each state? Or just one? For now, just one, but we can easily change this in the future if we want to test out the functionality with multiple agents per state.
             */
            Agent::factory()->create([
                'state_id' => $stateId,
            ]);
        }
    }
}
