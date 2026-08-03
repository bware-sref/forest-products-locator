<?php

namespace Database\Factories;

use App\Models\PageSeo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PageSeo>
 */
class PageSeoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Not constrained to PageSeoCrudController::MANAGED_ROUTES --
            // tests exercising the resolve()/route-name-matching behavior
            // just need a unique-ish key, not a real route.
            'route_name' => Str::slug($this->faker->unique()->words(2, true)),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(15),
            'og_image' => $this->faker->optional()->imageUrl(),
        ];
    }
}
