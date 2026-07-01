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
        Schema::table('imports', function (Blueprint $table) {
            //
            $table->foreignId('state_id')
                ->after('failed_rows')
                ->nullable()
                ->default(null)
                ->comment('The state in which the mills in this import are located.')
                ->constrained()
                ->cascadeOnUpdate()
                ->noActionOnDelete();

            $table->boolean('delete_from_state')
                ->after('state_id')                
                ->default(false)
                ->comment('Should any existing mills in this state be deleted?');
            
            /**
             * To differentiate between processed rows and imported rows!
             */
            $table->integer('imported_rows')
                ->after('total_rows')
                ->default(0)
                ->comment('importing happens before processing');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            //
            $table->dropForeign(['state_id']);
            $table->dropColumn([
                'delete_from_state',
                'imported_rows',
            ]);
        });
    }
};
