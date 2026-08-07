<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateForestProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateForestProduct>
 */
class StateForestProductFactory extends Factory
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
            'label' => fake()->randomElement([
                'Softwood Lumber',
                'Hardwood Lumber',
                'Poles',
                'Pulpwood',
                'Wood Chips',
                'Plywood',
                'OSB',
            ]),
            'sort_weight' => rand(0, 40),
        ];
    }
}
