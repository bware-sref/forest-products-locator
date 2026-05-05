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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();

            // name is the only field that is not required
            $table->string('name')
                ->nullable()
                ->default(null);
            $table->string('email');
            $table->string('subject');
            $table->text('message');
            $table->ipAddress('ip_address');
            // indicates if the mail has been sent successfully
            // failed sends can be retried periodically
            $table->boolean('sent')
                ->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
