<?php

namespace Database\Factories;

use App\Models\State;
use App\Models\StateForestryAgency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StateForestryAgency>
 */
class StateForestryAgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /**
         * Headline/CTA text intentionally doesn't reference a state name here:
         * definition() has no way to see a state_id supplied via ->for(), so
         * baking a name in from our own random pick could mismatch it (as it
         * did before this comment existed). Callers that care about
         * state-accurate text should override these on create().
         */
        return [
            'state_id' => State::inRandomOrder('')->first()->id,
            'headline' => 'Forestry Commission',
            'body' => fake()->paragraph(),
            'cta_1_label' => 'Visit the Forestry Commission',
            'cta_1_url' => fake()->url(),
            'cta_2_label' => 'Contact your State Representative',
            'cta_2_url' => '#contacts',
            'assistance_headline' => 'Available Assistance',
            'assistance_copy' => 'Find the programs and guidance most relevant to you.',
        ];
    }
}
