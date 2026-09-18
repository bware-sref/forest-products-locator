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
        Schema::table('mill_edits', function (Blueprint $table) {
            // 1. drop the existing foreign key constraint
            $table->dropForeign(['mill_id']);

            // 2. change the column to be nullable
            $table->foreignId('mill_id')
                ->nullable()
                ->default(null)
                ->change();

            // 3. re-add foreign key constraint along with cascade behavior
            // using foreignId('mill_id') twice causes a mysql error
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_edits', function (Blueprint $table) {
            // 1. drop the foreign key constraint
            $table->dropForeign(['mill_id']);

            // 2. revert however it was before
            $table->foreignId('mill_id')
                ->nullable(false)
                ->change();

            // 3. re-constrain + cascade behavior
            // using foreignId('mill_id') twice causes a mysql error
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills')
                ->cascadeOnDelete();
        });
    }
};
