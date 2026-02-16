<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            /**
             * Renaming mills.county to avoid conflicting with the Mill to County relationship attribute and method
             */
            // wrap this in a conditional so that we can update the original ingestion script and data to use county_name
            if (Schema::hasColumn('mills', 'county')) {
                $table->renameColumn('county', 'county_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            //
            if (Schema::hasColumn('mills', 'county_name')) {
                $table->renameColumn('county_name', 'county');
            }
        });
    }
};
