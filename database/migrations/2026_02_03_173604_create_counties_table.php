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
        Schema::create('counties', function (Blueprint $table) {
            $table->id();

            $table->string('name'); // e.g., Clarke 
            $table->string('type')
                ->nullable(true)
                ->default(null); // e.g. county, parish, municipalio
            $table->string('full_name')  // e.g. Clarke County
                ->nullable(true)
                ->default(null);

            /**
             * the data set included county_code and state_code columns
             * I decided to include county_code to facilitate matching against original data if needed
             * if nothing else it would be useful if we decide to ingest the geo_shape data from the same data source
             * on the other hand, the geo_shape data is UGLY and irregular, so we'd probably just treat it as JSON
             * Including state_code because it might help interfacing with other systems and datasets.
             */
            $table->string('county_code')
                ->nullable(true)
                ->default(null);
            $table->string('state_code')
                ->nullable(true)
                ->default(null);
            

            /**
             * one of the datasources I found has lat/lng coordinates as well as boundary polygons
             * the question is whether it's worth adding all that at present.
             * should lat & long be floats or strings?
             * floats seem right, but our existing data may indicate otherwise...
             * additionally, Mills.lat/long are strings
             */
            $table->string('latitude')
                ->nullable(true)
                ->default(null);
            $table->string('longitude')
                ->nullable(true)
                ->default(null);

            /**
             * We may never use this, but that's why it's nullable with default null
             */
            $table->string('geo_shape')
                ->nullable(true)
                ->default(null);
            
            /**
             * the data I found includes fips_code and gnis_code
             * don't know if they'll be useful to us but easy enough to include 
             * them.
             * fips_code and gnis_code need to be strings because they can have 
             * leading zeros.
             */
            // Federal Information Processing Standards
            $table->string('fips_code')
                ->nullable(true)
                ->default(null);
            // Geographic Names Information System
            $table->string('gnis_code')
                ->nullable(true)
                ->default(null);

            $table->foreignId('state_id')
                ->cascadeOnUpdate()
                ->cacadeOnDelete()
                ->constrained();
                
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('counties');
    }
};
