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
        Schema::create('mill_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            /**
             * icon and/or sprite seem like things we'd want to manage in the DB, but IDK if we need it now
             * maybe add them but make them nullable?
             * what values would you even store?
             * slugs?
             */
            // $table->string('icon');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mill_types');
    }
};
