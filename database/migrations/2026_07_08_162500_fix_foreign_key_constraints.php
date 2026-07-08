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
        /**
         * Okay!
         * Now we need a check for foreign key indices so we can run this in other environments.
         */
        $fkIndices = collect(Schema::getForeignKeys('mill_mill_type'))
            ->pluck('name')
            ->toArray();

        Schema::table('mill_mill_type', function (Blueprint $table) use ($fkIndices) {
            // drop the old foreignKey indices
            // fucker choked when both column names were in the same array
            // now it claims the foreign key index doesn't exist?!?

            // zOMG!
            // We dropped the foreignKey index but then the migration failed.
            // So when we re-ran it, the FK index was gone and thus that error.
            // $table->dropForeign('mill_mill_type_mill_id_foreign');
            
            $fks = [
                'mill_mill_type_mill_id_foreign',
                'mill_mill_type_mill_type_id_foreign',
            ];
            foreach ($fks as $fk) {
                if (in_array($fk, $fkIndices)) {
                    $table->dropForeign($fk);
                }
            }

            // now add them back with constrained before cascadeOnDelete
            // use foreign() instead of foreignId() because the latter creates a new column
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreign('mill_type_id')
                ->references('id')
                ->on('mill_types')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_mill_type', function (Blueprint $table) {
            // now we do almost the exact same thing
            $table->dropForeign([
                'mill_id',
            ]);
            $table->dropForeign([
                'mill_type_id',
            ]);

            /**
             * Reapply our old, incorrect foreign key indices without cascades.
             */
            $table->foreign('mill_id')
                ->references('id')
                ->on('mills');
            
            $table->foreign('mill_type_id')
                ->references('id')
                ->on('mill_types');
            
        });
    }
};
