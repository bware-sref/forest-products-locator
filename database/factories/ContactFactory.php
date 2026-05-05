<?php

namespace Database\Factories;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contact>
 */
class ContactFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // don't include name some of the time
            'name' => $this->faker->boolean(66.6) ? $this->faker->name() : '',
            'email' => $this->faker->email(),
            'subject' => $this->faker->words(rand(1, 6), true),
            'message' => $this->faker->paragraph(rand(1, 5), true),
            'ip_address' => $this->faker->ipv4(),
            'sent' => $this->faker->boolean(50),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
