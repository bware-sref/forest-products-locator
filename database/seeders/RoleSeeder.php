<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // read role data file and insert in DB
        $json = File::get(database_path('data/roles.json'));
        $data = json_decode($json, true);

        foreach ($data as $role) {
            $role['created_at'] = now();
            $role['updated_at'] = now();
            DB::table('roles')->updateOrInsert(
                ['name' => $role['name']], // lookup via
                 $role // values to updateOrInsert
            );
        }

        /**
         * Should we add role_has_permissions seeding here or in a separate seeder?
         * Separate!
         */
    }
}
