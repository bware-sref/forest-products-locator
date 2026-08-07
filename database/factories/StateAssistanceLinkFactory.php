<?php

namespace Database\Factories;

use App\Models\StateAssistanceCategory;
use App\Models\StateAssistanceLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateAssistanceLink>
 */
class StateAssistanceLinkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'state_assistance_category_id' => StateAssistanceCategory::inRandomOrder('')->first()->id,
            'label' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'url' => fake()->url(),
            'sort_weight' => rand(0, 40),
        ];
    }
}
