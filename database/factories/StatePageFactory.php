<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StatePage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatePage>
 */
class StatePageFactory extends Factory
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
            'hero_headline' => fake()->words(3, true).' Forest Products',
            'hero_img_dt' => null,
            'hero_img_mobile' => null,
            'hero_copy' => '<ul><li>'.fake()->sentence().'</li><li>'.fake()->sentence().'</li></ul>',
            'contacts_headline' => 'Want more information or a free site suitability analysis?',
            'contacts_copy' => fake()->sentence(),
        ];
    }
}
