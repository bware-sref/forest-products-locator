<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableName = 'faqs';
        $columnName = 'answer';

        if (!Schema::hasColumn($tableName, $columnName)) {
            Log::warning("\n{$tableName} does not have a column named '{$columnName}'. Aborting.");
        }

        $type = Schema::getColumnType($tableName, $columnName);
        if ('text' !== $type) {
            Schema::table($tableName, function (Blueprint $table) use ($columnName) {
                $table->text($columnName)->change();
            });
        } else {
            Log::debug("\n" . __FILE__ . ":\n{$tableName}.{$columnName} is already a column of type '{$type}'.");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /**
         * Don't rollback to varchar because we'll lose data!
         */

        Log::debug("\n" . __FILE__ . ":\nAcknowledging that down() executed without action.");
        // Schema::table('faqs', function (Blueprint $table) {
        //     //
        // });
    }
};
