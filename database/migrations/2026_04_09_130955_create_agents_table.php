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
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('title')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            /**
             * I'm not sure if state_id should be nullable or not.
             * Also, neither cascading on delete nor nulling on delete seems necessary because states are unlikely to be deleted, but nulling on delete is probably safer just in case.
             */
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('street_address')->nullable();
            $table->string('city')->nullable();
            $table->string('zip_code')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
