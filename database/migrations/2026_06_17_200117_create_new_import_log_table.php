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
        Schema::create("import_configs", function (Blueprint $table) {
            $table->id();
            /**
             * model_primary_key
             * model
             * config
             * and arguably delete_file_after_import
             * can/should be moved to the new import_(maps|mappings|settings) table
             */
            $table->string('model_primary_key')
                ->nullable(true)
                ->default(null);
            $table->text('model');
            $table->longText('config')->nullable();
            $table->boolean('delete_file_after_import')->default(false);

            $table->timestamps();
        });

        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            /**
             * Why isn't this a foreignId()?
             * Dunno.
             * Make that way if you think it should be.
             * It only needs to be nullable if the record is created before the user is known, which seems unlikely.
             */
            // $table->bigInteger('user_id')->index('imports_user_id')->nullable();
            $table->foreignId('user_id')->nullable()->default(null)->constrained();
            $table->text('file_path');
            /**
             * Add column for original file name so we can keep track of uploads.
             */
            $table->text('original_file_name');
            $table->string('disk')->default('local');

            /**
             * Add columns for total rows, processed rows, and failed rows.
             */
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('failed_rows')->default(0);

            /**
             * Add column for import_map_id
             * import_configs?
             * import_settings?
             * And I'm not sure we want it to be nullable...
             * Well, we need it to be nullable in case this record is created before the config record.
             */
            $table->foreignId('import_config_id')->nullable()->default(null)->constrained();
            
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
        Schema::dropIfExists('import_configs');
        Schema::dropIfExists('imports');
    }
};
