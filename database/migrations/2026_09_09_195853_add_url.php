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
            $table->text('url')
                ->after('submitter_ip');

            $table->string('approve_hash')
                ->nullable()
                ->default(null)
                ->change();

            $table->string('reject_hash')
                ->nullable()
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
            $table->dropColumn(['url']);

            $table->string('approve_hash')
                ->nullable(false)
                ->change();
            $table->string('reject_hash')
                ->nullable(false)
                ->change();
        });
    }
};
