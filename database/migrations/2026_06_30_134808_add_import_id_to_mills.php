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
            /**
             * If a mill was added via import, we capture the import_id.
             */
            $table->foreignId('import_id')
                ->after('modification_date')
                ->nullable()
                ->default(null)
                ->constrained()
                ->noActionOnUpdate()
                ->noActionOnDelete();

            /**
             * and the user
             * note that we're preserving the foreignKeys on updates and deletes
             */
            $table->foreignId('user_id')
                ->after('modification_date')
                ->nullable()
                ->default(null)
                ->constrained()
                ->noActionOnUpdate()
                ->noActionOnDelete();

            /**
             * I'm tempted to add other columns that appear in some states' data now, but I'm going to wait until I see more 
             * data.
             * 
             * products - text  (Georgia)
             * by_products  - text  (Georgia)
             * contact_name  - string   (Florida)
             * phone2 (distinct from fax)   - string    (GA, FL)
             * email2   - string    (GA, FL)
             * actual_wood_species (or woods)   - text  (GA)
             */
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            //
            $table->dropForeign([
                'import_id',
                'user_id',
            ]);
        });
    }
};
