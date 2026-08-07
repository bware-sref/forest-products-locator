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
        Schema::create('state_forestry_agencies', function (Blueprint $table) {
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

            $table->string('cta_1_label')->nullable()->default(null);
            $table->string('cta_1_url')->nullable()->default(null);
            $table->string('cta_2_label')->nullable()->default(null);
            $table->string('cta_2_url')->nullable()->default(null);

            $table->string('assistance_headline')
                ->nullable()
                ->default(null);
            $table->text('assistance_copy')
                ->nullable()
                ->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_forestry_agencies');
    }
};
