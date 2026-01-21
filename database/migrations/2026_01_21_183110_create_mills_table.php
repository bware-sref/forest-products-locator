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
        Schema::create('mills', function (Blueprint $table) {
            $table->id();
            /**
             * Columns from FPL/N export
             * Resist the urge (for now at least) to break addresses out into a separate table.
             * Allow every column except match_id to be nullable and default to null.
             */
            $table->string('match_id');
            $table->string('mill_id')
                ->nullable($value = true)
                ->default(null);
            $table->string('mill_name')
                ->nullable($value = true)
                ->default(null);
            $table->string('latitude')
                ->nullable($value = true)
                ->default(null);
            $table->string('longitude')
                ->nullable($value = true)
                ->default(null);
            $table->string('year')
                ->nullable($value = true)
                ->default(null);
            $table->string('physical_address')
                ->nullable($value = true)
                ->default(null);
            $table->string('physical_city')
                ->nullable($value = true)
                ->default(null);
            $table->string('county')
                ->nullable($value = true)
                ->default(null);
            $table->string('physical_state')
                ->nullable($value = true)
                ->default(null);
            $table->string('physical_zip')
                ->nullable($value = true)
                ->default(null);
            $table->string('mailing_address')
                ->nullable($value = true)
                ->default(null);
            $table->string('mailing_city')
                ->nullable($value = true)
                ->default(null);
            $table->string('mailing_state')
                ->nullable($value = true)
                ->default(null);
            $table->string('mailing_zip')
                ->nullable($value = true)
                ->default(null);
            $table->string('telephone')
                ->nullable($value = true)
                ->default(null);
            $table->string('fax')
                ->nullable($value = true)
                ->default(null);
            $table->string('type')
                ->nullable($value = true)
                ->default(null);
            $table->string('species')
                ->nullable($value = true)
                ->default(null);
            $table->string('email')
                ->nullable($value = true)
                ->default(null);
            $table->string('web_site')
                ->nullable($value = true)
                ->default(null);
            $table->string('size')
                ->nullable($value = true)
                ->default(null);
            $table->string('modification_date')
                ->nullable($value = true)
                ->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mills');
    }
};
