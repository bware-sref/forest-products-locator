<?php

namespace Database\Factories;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $publishedAt = $this->faker->dateTimeBetween('-5 years', '+2 hours');
        $question = $this->faker->sentence(8);
        
        return [
            //
            'question' => $question,
            'answer' => $this->faker->sentences(rand(1, 5), true),
            'slug' => Str::slug($question),
            'order' => $this->faker->numberBetween(1, 50),
            // empty seed to shut up intelliphense
            'faq_category_id' => FaqCategory::inRandomOrder('')->first()->id ?? FaqCategory::factory(),
            'published_at' => $publishedAt,
            'unpublished_at' => $this->faker->boolean(0.1) ? $this->faker->dateTimeBetween($publishedAt, '+2 months') : null,
        ];
    }
}
