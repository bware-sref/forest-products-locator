<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class WoodSpeciesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // read wood species data file and insert in DB
        $json = File::get(database_path('data/getMillSpecies.json'));
        $data = json_decode($json, true);

        foreach ($data['species'] as $species) {
            $species = [
                'name' => $species,
                'created_at' => now(),
                'updated_at' => now(),
            ];
            DB::table('wood_species')->updateOrInsert(
                ['name' => $species['name']], // lookup via
                 $species // values to updateOrInsert
            );
        }
        //
    }
}
