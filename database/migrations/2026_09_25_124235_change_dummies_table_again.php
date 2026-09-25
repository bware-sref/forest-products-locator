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
        Schema::table('dummies', function (Blueprint $table) {
            //
            $table->string('expectations')
                ->after('victuals')
                ->nullable()
                ->default(null);

            throw new \Exception("Bustin' Loose! starring Richard Pryor and Gene Wilder!");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dummies', function (Blueprint $table) {
            //
            $table->dropColumn(['expectations']);
        });
    }
};
