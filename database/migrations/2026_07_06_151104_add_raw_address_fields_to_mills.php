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
        Schema::table('mills', function (Blueprint $table) {
            // raw address fields hold originally imported addresses before validation and normalization
            $table->string('raw_physical_address')
                ->after('year')
                ->nullable(true)
                ->default(null);

            $table->string('raw_mailing_address')
                ->after('physical_zip')
                ->nullable(true)
                ->default(null);
            
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            //
            $table->dropColumn([
                'raw_physical_address',
                'raw_mailing_address',
            ]);
        });
    }
};
