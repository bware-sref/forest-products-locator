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
        Schema::create('mill_wood_species', function (Blueprint $table) {
            $table->id();

            /**
             * @FYI
             * constrained() must come before cascadeOnX() to work properly!!!
             * The ordering below creates the foreign key index, but it doesn't
             * apply the cascades in MySQL.
             */
            $table->foreignId('mill_id')
                ->cascadeOnUpdate()
                ->cascaseOnDelete()
                ->constrained();
                
            /**
             * @FYI
             * constrained() must come before cascadeOnX() to work properly!!!
             * The ordering below creates the foreign key index, but it doesn't
             * apply the cascades in MySQL.
             */
            $table->foreignId('wood_species_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->constrained();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mill_wood_species');
    }
};
