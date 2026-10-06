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

        /**
         * Make sure we're using the correct guard.
         * FFS, changing the guard to backpack broke a lot of stuff.
         * We should just use whatever has been configured to be the default.
         */
        // $guard = function_exists('backpack_guard_name') ? backpack_guard_name() : config('auth.defaults.guard');
        $guard = config('auth.defaults.guard');

        foreach ($data as $role) {
            $role['guard_name'] = $guard;
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
