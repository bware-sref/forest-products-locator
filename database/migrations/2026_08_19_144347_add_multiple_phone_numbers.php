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
        Schema::table('state_contacts', function (Blueprint $table) {
            //
            $table->string('phone_label')
                ->nullable()
                ->default(null)
                ->after('phone');
            $table->string('phone_2')
                ->nullable()
                ->default(null)
                ->after('phone_label');
            $table->string('phone_2_label')
                ->nullable()
                ->default(null)
                ->after('phone_2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('state_contacts', function (Blueprint $table) {
            //
            $table->dropColumn([
                'phone_label',
                'phone_2',
                'phone_2_label',
            ]);
        });
    }
};
