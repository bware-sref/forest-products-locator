<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * The example on Backpack Permissions Manager github manually specifies the tables to create permissions for.
         * That makes sense, but seems tedious.
         * So what exactly do we need?
         * Basic "see" and "edit" levels for most models.
         * Mills need a few extra operations.
         *  - approve (or is this MillEdits model?)
         *  - import
         * Also, triggering emails to mills needs permission, so maybe mills.email?
         * Further, mills.import may need to be split...although if admins can import from spreadsheets and external sites, 
         * they don't need to be differentiated in permissions.
         * What models need to have permissions assigned?
         * What permissions does each model need?
         * 
         * Also also, since model.see is included in model.edit, do we need to use canAny()?
         * 
         * Also also also, Spatie PermissionsManager docs suggest another approach to seeding permissions and roles.
         */

        // read permission data file and insert in DB
        $json = File::get(database_path('data/permissions.json'));
        $data = json_decode($json, true);

        foreach ($data as $permission) {
            $permission['created_at'] = now();
            $permission['updated_at'] = now();
            DB::table('permissions')->updateOrInsert(
                ['name' => $permission['name']], // lookup via
                 $permission // values to updateOrInsert
            );
        }
    }
}
