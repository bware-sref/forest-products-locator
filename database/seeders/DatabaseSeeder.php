<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\StateSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'bware@sref.info'],
            [
                'name' => 'Bob Ware',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        $this->call([
            StateSeeder::class,
        ]);
    }
}
