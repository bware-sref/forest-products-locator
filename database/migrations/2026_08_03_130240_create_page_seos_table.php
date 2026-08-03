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
        Schema::create('page_seos', function (Blueprint $table) {
            $table->id();
            /**
             * Matches the Laravel route name (e.g. 'about-us', 'mill-list')
             * for the static page this record overrides metadata for.
             */
            $table->string('route_name')->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            /**
             * Absolute or app-relative URL. Falls back to the site default
             * (see config/seo.php) when null.
             */
            $table->string('og_image')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_seos');
    }
};
