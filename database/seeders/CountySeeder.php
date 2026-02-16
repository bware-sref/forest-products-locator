<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


class CountySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // need to query State data to populate state_id fields
        // maybe abort if state data isn't present?
        $states = DB::table('states')
            ->select('id', 'name')
            ->get();
        $states = collect($states)->keyBy('name');

        // read county data file and insert in DB
        $json = File::get(database_path('data/us-counties-by-state__only-south.json'));
        // $json = File::get(database_path('data/alabama-counties.json'));
        $data = json_decode($json, true);

        $successes = 0;
        $fails = 0;

        foreach ($data as $county) {
            $stateName = $county['state_name'];

            $county['created_at'] = now();
            $county['updated_at'] = now();
            $county['state_id'] = $states[$county['state_name']]->id;

            // unset state_name to prevent DB error
            unset($county['state_name']);

            $yes = DB::table('counties')->updateOrInsert(
                [
                    'name' => $county['name'],
                    'state_code' => $county['state_code'],
                ], // lookup via
                $county // values to updateOrInsert
            );

            if ($yes) {
                $successes++;
            } else {
                Log::error(sprintf(
                    'FAILED to updatedOrInserted %s for state %s',
                    $county['name'],
                    $stateName
                ));
                $fails++;
            }
        }

        Log::info(sprintf(
            'CountySeeder done. %d successful, %d failed.',
            $successes,
            $fails
        ));
    }
}
