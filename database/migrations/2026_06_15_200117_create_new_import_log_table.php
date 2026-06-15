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
        Schema::create('fpl_import_log', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id')->index('fpl_import_log_user_id')->nullable();
            $table->text('file_path');
            $table->string('disk')->default('local');

            /**
             * model_primary_key
             * model
             * config
             * and arguably delete_file_after_import
             * can/should be moved to the new import_(mappings|settings) table
             */
            $table->string('model_primary_key')
                ->nullable(true)
                ->default(null);
            $table->text('model');
            $table->longText('config')->nullable();
            $table->boolean('delete_file_after_import')->default(false);
            
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_log');
    }
};
