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
            // add foreign key for mailing_state_id
            $table->foreignId('mailing_state_id')
                ->after('mailing_state')
                // We can't use constrained() because we allow null values (since our data is incomplete).
                // ->constrained(
                //     table: 'states',
                //     column: 'id'
                // )
                ->nullable(true)
                ->default(null)
                ->cascadeOnUpdate();

            // add foreign key for mailing_county_id
            $table->foreignId('mailing_county_id')
                ->after('mailing_state_id')
                // We can't use constrained() because we allow null values (since our data is incomplete).
                // ->constrained(
                //     table: 'counties',
                //     column: 'column'
                // )
                ->nullable(true)
                ->default(null)
                ->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            /**
             * We may have to use dropForeign() since we used constrained().
             * For now, let's just use dropColumn([]) instead and see what happens.
             */
            $table->dropColumn([
                'mailing_state_id',
                'mailing_county_id',
            ]);
        });
    }
};
