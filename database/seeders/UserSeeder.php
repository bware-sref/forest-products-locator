<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
        User::firstOrCreate(
            ['email' => 'ddrummond@sref.info'],
            [
                'name' => 'Daniel Drummond',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        //
    }
}
