<?php

namespace Database\Factories;

use App\Models\Agent;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    /**
     * List of southern states for generating realistic agent data.
     * Haha, AI initially included West Virginia in the list of southern states.
     * I guess it is technically correct because it's south of the Mason-Dixon line.
     * But I don't think most people would consider it a southern state because it seceded from
     * Virginia during the Civil War to stay in the Union.
     * Maryland is also technically a southern state, but generally only people who know what the Mason-Dixon line is know that.
     */
    protected $southernStates = ['AL', 'AR', 'FL', 'GA', 'KY', 'LA', 'MS', 'NC', 'OK', 'SC', 'TN', 'TX', 'VA'];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'title' => $this->faker->jobTitle(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber(),
            'state_id' => $this->faker->randomElement(
                State::whereIn('abbreviation', $this->southernStates)->pluck('id')->toArray()
            ),
            'street_address' => $this->faker->streetAddress(),
            'city' => $this->faker->city(),
            'zip_code' => $this->faker->postcode(),
        ];
    }
}
