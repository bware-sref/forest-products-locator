<?php

use App\Enums\PublicationStatus;
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
        Schema::create('state_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('state_id')
                ->nullable(false)
                ->cascadeOnDelete();
            $table->string('title');
            $table->text('content')
                ->nullable(true)
                ->default(null);
            $table->integer('sort_order')
                ->default(10);
            
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Pending);            

            // I'm debating added a user_id field to this model.

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_resources');
    }
};
