<?php

use App\Enums\MillRawImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mill_raw_imports', function (Blueprint $table) {
            $table->id();

            // Every raw import row belongs to an import run.
            $table->foreignId('import_id')
                /**
                 * index() is not necessary on FKs in MySQL because it handles it automatically, but it's required for PostgreSQL.
                 * Gemini claims it's safe to include when using MySQL and that this is the correct way
                 * to write DB-agnostic migrations.
                 * We'll see. 
                 */
                ->index()
                ->constrained('imports')
                ->cascadeOnDelete();

            // The feature's own ID from the source data.
            // Matches OBJECTID, FID, or row index depending on the state.
            // Stored as string to accommodate all formats without coercion.
            /**
             * Not sure why we need this outside of the geojson column, but oh well.
             */
            $table->string('raw_feature_id', 100)
                ->nullable()
                ->default(null)
                ->comment('Feature ID from ArcGIS data');

            // Full GeoJSON feature object — geometry + properties — exactly
            // as received from the API or file. Never modified after insert.
            $table->json('geojson');

            // Populated by the background job after the mill row is created
            // or updated. Null while status = 'pending' or 'failed'.
            $table->foreignId('mill_id')
                ->nullable()
                ->default(null)
                /**
                 * index() is not necessary on FKs in MySQL because it handles it automatically, but it's required for PostgreSQL.
                 * Gemini claims it's safe to include when using MySQL and that this is the correct way
                 * to write DB-agnostic migrations.
                 * We'll see. 
                 */
                ->index()
                ->constrained('mills')
                ->nullOnDelete();

            // PHP Backed Enum: 'pending' | 'processed' | 'failed'
            $table->string('status')
                // ->default('pending');
                ->default(MillRawImportStatus::Pending)
                /**
                 * chain index() here instead of adding in a separate statement
                 */
                ->index();

            /**
             * singular name seem stupids
             */
            // $table->text('error_message')
            $table->text('errors')
                ->nullable()
                ->default(null);

            $table->timestamps();

            // Allows efficient lookup of all raw imports for a given mill
            // (import history) and all unprocessed rows for a given run.
            // $table->index(['import_id', 'status']);
            // $table->index('mill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mill_raw_imports');
    }
};
