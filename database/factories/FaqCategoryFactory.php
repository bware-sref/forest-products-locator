<?php

namespace Database\Factories;

use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<FaqCategory>
 */
class FaqCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title($this->faker->words(rand(1,3), true));
        return [
            'name' => $name,
            /**
             * I'm debating adding slug fields to FaqCategory and Faq so they can be directly linked more easily.
             */
            'slug' => Str::slug($name),
            'order' => $this->faker->numberBetween(1, 50),
        ];
    }
}
