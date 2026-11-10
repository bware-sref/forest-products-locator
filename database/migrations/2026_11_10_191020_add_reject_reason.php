<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mill_edits', function (Blueprint $table) {
            //
            $table->text('rejection_reason')
                ->after('status')
                ->nullable(true)
                ->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_edits', function (Blueprint $table) {
            //
            $table->dropColumn(['rejection_reason']);
        });
    }
};
