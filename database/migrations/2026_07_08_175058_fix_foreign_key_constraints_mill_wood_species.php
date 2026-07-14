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
        Schema::table('mill_wood_species', function (Blueprint $table) {
            //
            $table->dropForeign(['mill_id']);
            $table->dropForeign(['wood_species_id']);

            // now add them back with constrained before cascadeOnDelete
            // use foreign() instead of foreignId() because the latter creates a new column
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('wood_species_id')
                ->references('id')
                ->on('wood_species')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_wood_species', function (Blueprint $table) {
            /**
             * for a proper roll back, we again have to strip the FK indices so that we remove
             * the cascade actions
             */
            $table->dropForeign(['mill_id']);
            $table->dropForeign(['wood_species_id']);

            /**
             * now add them back without adding the cascades
             */
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills');

            $table->foreign('wood_species_id')
                ->references('id')
                ->on('wood_species');            
        });
    }
};
