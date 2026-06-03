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
        Schema::table('users', function (Blueprint $table) {
            // ultimately, it's simpler to add state_id to users instead of building a bunch of crap we probably won't need
            $table->foreignId('state_id')
                ->after('remember_token')
                ->comment('for state agents')
                ->nullable(true)
                ->default(null)
                ->cascadeOnUpdate();
            
            $table->string('title')
                ->after('name')
                ->nullable(true)
                ->default(null)
                ->comment('mostly for state agents');

            $table->string('phone')
                ->after('email')
                ->nullable(true)
                ->default(null)
                ->comment('mostly for state agents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            $table->dropColumn([
                'state_id',
                'title',
                'phone',
            ]);
        });
    }
};
