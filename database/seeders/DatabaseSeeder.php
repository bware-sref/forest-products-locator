<?php

namespace Database\Seeders;

// use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::firstOrCreate(
        //     ['email' => 'bware@sref.info'],
        //     [
        //         'name' => 'Bob Ware',
        //         'password' => 'password',
        //         'email_verified_at' => now(),
        //     ]
        // );
        // User::firstOrCreate(
        //     ['email' => 'ddrummond@sref.info'],
        //     [
        //         'name' => 'Daniel Drummond',
        //         'password' => 'password',
        //         'email_verified_at' => now(),
        //     ]
        // );

        $this->call([
            UserSeeder::class,
            StateSeeder::class,
            MillTypeSeeder::class,
            WoodSpeciesSeeder::class,
        ]);
    }
}
