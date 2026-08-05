<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateEconomicImpact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateEconomicImpact>
 */
class StateEconomicImpactFactory extends Factory
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
            'headline' => 'Forestry Economic Impact',
            'stat_1_label' => 'In Annual Economic Impact',
            'stat_1_value' => '$'.fake()->numberBetween(5, 50).' billion',
            'stat_2_label' => 'Jobs Supported by the Timber Industry',
            'stat_2_value' => fake()->numberBetween(5, 60).',000+',
            'stat_3_label' => 'Average Annual Industry Investment',
            'stat_3_value' => '$'.fake()->numberBetween(100, 900).' million',
        ];
    }
}
