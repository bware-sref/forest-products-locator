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
             * we didn't create these columns properly in the beginning because there are no indices
             * for states or counties on mills
             */
            $table->foreign('state_id')
                ->references('id')
                ->on('states')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('county_id')
                ->references('id')
                ->on('counties')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('mailing_state_id')
                ->references('id')
                ->on('states')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /**
             * We probably want to index more columns, 
             * e.g., mill_name, lat & long, physical_address, etc.
             * But we can do that in another migration. :-D
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            //
            $table->dropForeign([
                'state_id',
                'county_id',
                'mailing_state_id',
            ]);
        });
    }
};
