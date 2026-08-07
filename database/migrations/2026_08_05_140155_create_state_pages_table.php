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
        Schema::create('state_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /**
             * The hero is the only section guaranteed to be present on every state page,
             * so it lives directly on state_pages rather than its own section table.
             * Everything else (forest overview, economic impact, forestry agency, etc.)
             * gets its own table, keyed by state_id, since presence/arity varies per state.
             */
            $table->string('hero_headline')
                ->nullable()
                ->default(null);
            $table->string('hero_img_dt')
                ->nullable()
                ->default(null);
            $table->string('hero_img_mobile')
                ->nullable()
                ->default(null);

            /**
             * Rich text rather than a fixed bullet list, so hero copy isn't
             * locked into one shape for every state.
             */
            $table->text('hero_copy')
                ->nullable()
                ->default(null);

            $table->string('contacts_headline')
                ->nullable()
                ->default(null);

            $table->string('contacts_copy')
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
        Schema::dropIfExists('state_pages');
    }
};
