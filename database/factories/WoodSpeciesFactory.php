<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WoodSpecies>
 */
class WoodSpeciesFactory extends Factory
{

    private static ?array $species = null;

    private static function species(): array
    {
        return self::$species ??= json_decode(
            File::get(database_path('data/getMillSpecies.json')),
            true
        )['species'];
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $species = fake()->unique()->randomElement(self::species());

        return [
            'name' => $species,
        ];
    }
}
