<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateForestType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateForestType>
 */
class StateForestTypeFactory extends Factory
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
                'Pine Forests',
                'Mixed Pine-Hardwood Forests',
                'Upland Hardwoods',
                'Bottomland Hardwoods',
            ]),
            'description' => fake()->sentence(),
            'icon' => null,
            'sort_weight' => rand(0, 40),
        ];
    }
}
