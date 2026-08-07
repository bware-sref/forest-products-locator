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
        Schema::create('state_forest_overviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('headline')
                ->nullable()
                ->default(null);
            $table->text('body')
                ->nullable()
                ->default(null);
            $table->string('image')
                ->nullable()
                ->default(null);

            /**
             * Up to 4 stat pairs (kept even so they align in a 2-column grid).
             * Fixed columns for now since the layout caps the count; revisit as
             * a hasMany if that cap turns out to be wrong.
             */
            $table->string('stat_1_label')->nullable()->default(null);
            $table->string('stat_1_value')->nullable()->default(null);
            $table->string('stat_2_label')->nullable()->default(null);
            $table->string('stat_2_value')->nullable()->default(null);
            $table->string('stat_3_label')->nullable()->default(null);
            $table->string('stat_3_value')->nullable()->default(null);
            $table->string('stat_4_label')->nullable()->default(null);
            $table->string('stat_4_value')->nullable()->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_forest_overviews');
    }
};
