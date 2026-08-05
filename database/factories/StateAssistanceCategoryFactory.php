<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateAssistanceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateAssistanceCategory>
 */
class StateAssistanceCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state_id' => State::inRandomOrder('')->first()->id,
            'title' => fake()->randomElement([
                'Industry & Site Selection',
                'Loggers, Buyers & Suppliers',
                'Educators & Researchers',
                'Timberland Owners',
            ]),
            'description' => fake()->sentence(),
            'sort_weight' => rand(0, 40),
        ];
    }
}
