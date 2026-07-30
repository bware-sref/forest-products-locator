<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleHasPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // read role data file and insert in DB
        $json = File::get(database_path('data/permissions-by-role.json'));
        $data = json_decode($json, true);

        /**
         * We need ids to use attach or sync
         */

        foreach ($data as $roleName => $permissionNames) {
            if (empty($permissionNames)) {
                continue;
            }
            $role = Role::where('name', $roleName)->first();
            $permissions = Permission::select('id')
                ->whereIn('name', $permissionNames)
                ->get();
            $pIds = collect($permissions->toArray())
                ->flatten()
                ->toArray();
            
            $role->permissions()->sync($pIds);
        }
    }
}
