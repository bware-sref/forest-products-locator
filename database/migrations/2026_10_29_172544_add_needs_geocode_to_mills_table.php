<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Set when a mill's physical address changes without new coordinates (see Mill::booted()), so GeocodeMill knows
     * the existing coordinates may be stale. Cleared by GeocodeMill after a successful lookup.
     */
    public function up(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            $table->boolean('needs_geocode')
                ->default(false)
                ->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            $table->dropColumn('needs_geocode');
        });
    }
};
