<?php

namespace Database\Factories;

use App\Enums\PublicationStatus;
use App\Models\State;
use App\Models\StateResource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StateResource>
 */
class StateResourceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'state_id' => State::inRandomOrder('')->first()->id,
            'title' => Str::ucwords(fake()->words(rand(2,5), true)),
            'content' => Str::ucfirst(fake()->paragraphs(rand(1, 4), true)),
            'status' => fake()->randomElement(PublicationStatus::cases()),
            'sort_weight' => rand(0, 40),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
