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
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('question');
            $table->text('answer');
            $table->string('slug');
            /**
             * weighted sort
             */
            $table->integer('order')
                ->default(50);
            /**
             * FAQ categories seem useful, but then we'd have to manage the categories.
             */
            $table->foreignId('faq_category_id')
                ->nullable(true)
                ->default(null)
                ->constrained()
                ->nullOnDelete();
            /**
             * Instead of PublicationStatus, we could use a published_at field to allow simple scheduled publication
             */
            $table->timestamp('published_at')
                ->useCurrent();
            $table->timestamp('unpublished_at')
                ->nullable(true)
                ->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
