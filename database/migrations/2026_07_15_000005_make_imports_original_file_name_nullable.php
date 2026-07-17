<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /** 
         * @WTF Schema::closure() and DB::statement() will not work properly in the same migration!!!
         * Force MySQL directly to accept NULL
         * Gemini claims text columns don't need to have default null.
         */
        DB::statement('ALTER TABLE `imports` MODIFY `original_file_name` TEXT NULL;');
    }

    public function down(): void
    {
        /**
         * Remove nullable, default null
        */
        DB::statement('ALTER TABLE `imports` MODIFY `original_file_name` TEXT NOT NULL;');
    }
};
