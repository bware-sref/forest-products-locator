<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\State>
 */
class StateFactory extends Factory
{
    private static ?array $stateData = null;

    private static function stateData(): array
    {
        return self::$stateData ??= json_decode(
            File::get(database_path('data/states-with-coordinates.json')),
            true
        );
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $state = fake()->unique()->randomElement(self::stateData());

        return [
            'name' => $state['name'],
            'slug' => Str::slug($state['name']),
            'abbreviation' => $state['abbreviation'],
            'latitude' => $state['latitude'],
            'longitude' => $state['longitude'],
        ];
    }
}
