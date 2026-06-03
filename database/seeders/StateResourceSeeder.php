<?php

namespace Database\Seeders;

use App\Enums\PublicationStatus;
use App\Models\State;
use App\Models\StateResource;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateResourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * We could find states with mills and add resources...
         * or we could just use the list of Southern states on the State model...
         * except that list is only state abbreviations
         */
        $millStates = State::getMillStates(false);
        // dd($millStates);
        foreach ($millStates as $state) {
            StateResource::factory()
                ->count(fake()->numberBetween(3, 10))
                ->create([
                    'state_id' => $state->id,
                    'status' => PublicationStatus::Approved,
                ]);
        }
    }
}
