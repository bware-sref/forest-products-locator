<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            StateSeeder::class,
            CountySeeder::class,
            MillTypeSeeder::class,
            WoodSpeciesSeeder::class,
            AgentSeeder::class,
            FaqCategorySeeder::class,
            /**
             * FAQs seeded by FaqCategorySeeder
             */
            // FaqSeeder::class,
            StatePageSeeder::class,
            StateContactSeeder::class,
            StateForestOverviewSeeder::class,
            StateForestTypeSeeder::class,
            StateForestProductSeeder::class,
            StateEconomicImpactSeeder::class,
            StateForestryAgencySeeder::class,
            StateAssistanceCategorySeeder::class,
            /**
             * Assistance links seeded by StateAssistanceCategorySeeder
             */
            // StateAssistanceLinkSeeder::class,
        ]);
    }
}
