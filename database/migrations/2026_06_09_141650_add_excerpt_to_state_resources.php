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
        Schema::table('state_resources', function (Blueprint $table) {
            //
            $table->text('teaser')
                ->nullable(true)
                ->default(null)
                ->comment('The teaser is the text displayed in the list view.');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_resources', function (Blueprint $table) {
            //
            $table->dropColumn(['teaser']);
        });
    }
};
