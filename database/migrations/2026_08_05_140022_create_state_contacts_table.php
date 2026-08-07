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
        Schema::create('state_contacts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('state_id')
                ->index()
                ->constrained()
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('name')
                ->nullable()    
                ->default(null);
            $table->string('title')
                ->nullable()    
                ->default(null);
            $table->text('address')
                ->nullable()    
                ->default(null);
            $table->string('phone')
                ->nullable()    
                ->default(null);
            $table->string('email')
                ->nullable()    
                ->default(null);

            $table->integer('sort_weight')
                ->default(10)
                ->comment('small values float; large values sink');
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('state_contacts');
    }
};
