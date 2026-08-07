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
        Schema::create('state_economic_impacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('headline')
                ->nullable()
                ->default(null);

            /**
             * Exactly 3 stat pairs, fixed by the design's layout.
             */
            $table->string('stat_1_label')->nullable()->default(null);
            $table->string('stat_1_value')->nullable()->default(null);
            $table->string('stat_2_label')->nullable()->default(null);
            $table->string('stat_2_value')->nullable()->default(null);
            $table->string('stat_3_label')->nullable()->default(null);
            $table->string('stat_3_value')->nullable()->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_economic_impacts');
    }
};
