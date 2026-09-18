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
        Schema::table('mill_edits', function (Blueprint $table) {
            //
            $table->boolean('sent')
                ->default(false)
                ->after('proposed_changes');
            $table->string('sent_to')
                ->nullable(true)
                ->default(null)
                ->after('sent');

            /**
             * Should we also make url nullable?
             * Probably.
             * That will mean we don't have to set a nonsense string during the creating event to allow it to save.
             */
            $table->text('url')
                ->nullable(true)
                ->default(null)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_edits', function (Blueprint $table) {
            //
            $table->dropColumn(['sent', 'sent_to']);

            $table->text('url')
                ->change();
        });
    }
};
