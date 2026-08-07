<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateForestOverview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateForestOverview>
 */
class StateForestOverviewFactory extends Factory
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
            'headline' => 'Forest Acreage & Land Cover',
            'body' => fake()->paragraph(),
            'image' => null,
            'stat_1_label' => 'Total Forest Acres',
            'stat_1_value' => fake()->numberBetween(5, 30).' million',
            'stat_2_label' => 'Forest Land Cover',
            'stat_2_value' => fake()->numberBetween(40, 90).'%',
            'stat_3_label' => 'Private Forestland',
            'stat_3_value' => fake()->numberBetween(60, 99).'%',
            'stat_4_label' => null,
            'stat_4_value' => null,
        ];
    }
}
