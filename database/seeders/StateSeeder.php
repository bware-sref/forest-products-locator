<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // read state data file and insert in DB
        $json = File::get(database_path('data/state-names-and-abbreviations.json'));
        $data = json_decode($json, true);

        foreach ($data as $state) {
            $state['created_at'] = now();
            $state['updated_at'] = now();
            DB::table('states')->updateOrInsert(
                ['name' => $state['name']], // lookup via
                 $state // values to updateOrInsert
            );
        }
    }
}
