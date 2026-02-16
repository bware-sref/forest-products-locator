<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MillTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // read MillType data file and insert in DB
        $json = File::get(database_path('data/getMillTypes.json'));
        $data = json_decode($json, true);

        foreach ($data['types'] as $millType) {
            $millType = [
                'name' => $millType,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('mill_types')->updateOrInsert(
                ['name' => $millType['name']], // lookup via
                $millType, // values to updateOrInsert
            );
        }
    }
}
