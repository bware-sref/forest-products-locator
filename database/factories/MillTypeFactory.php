<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MillType>
 */
class MillTypeFactory extends Factory
{
    private static ?array $types = null;

    private static function types(): array
    {
        return self::$types ??= json_decode(
            File::get(database_path('data/getMillTypes.json')),
            true
        )['types'];
    }


    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->unique()->randomElement(self::types());

        return [
            'name' => $type,
        ];
    }
}
