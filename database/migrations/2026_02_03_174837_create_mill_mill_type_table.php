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
        Schema::create('mill_mill_type', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mill_id')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->constrained();

            $table->foreignId('mill_type_id')
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
        Schema::dropIfExists('mill_mill_type');
    }
};
