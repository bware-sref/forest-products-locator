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
            // add foreign keys for county and state
            $table->foreignId('county_id')
                ->after('county')
                ->nullable(true)
                ->default(null)
                ->cascadeOnUpdate();

            $table->foreignId('state_id')
                ->after('physical_state')
                ->nullable(true)
                ->default(null)
                ->cascadeOnUpdate();

            // our other relationships are many to many and thus use intermediate tables

            /**
             * Add an index to match_id
             */
            $table->unique('match_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            // apparently we have to drop the foreign key constraints and then drop the columns
            // Or do we?
            // $table->dropForeign(['county_id']);
            // $table->dropForeign(['state_id']);

            $table->dropColumn([
                'county_id',
                'state_id',
            ]);

            // remove the index and unique constraint
            $table->dropUnique(['match_id']);
        });
    }
};
