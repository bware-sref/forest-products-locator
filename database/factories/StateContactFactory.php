<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateContact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateContact>
 */
class StateContactFactory extends Factory
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
            'name' => fake()->name(),
            'title' => fake()->jobTitle(),
            'address' => fake()->streetAddress()."\n".fake()->city().', '.fake()->stateAbbr().' '.fake()->postcode(),
            'phone' => fake()->numerify('(###) ###-####'),
            'email' => fake()->safeEmail(),
            'sort_weight' => rand(0, 40),
        ];
    }
}
