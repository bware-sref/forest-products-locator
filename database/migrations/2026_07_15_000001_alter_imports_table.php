<?php

use App\Enums\ImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            // Full ArcGIS query URL + query string.
            // Null indicates a file upload rather than an API pull.
            $table->text('api_url')
                ->nullable()
                ->default(null)
                ->after('original_file_name');

            // PHP Backed Enum: 'arcgis' | 'xlsx'
            // Derivable from api_url / original_file_name but stored
            // explicitly for query convenience.
            /**
             * I think we ditch this one, too.
             * If anything, we could make an accessor to derive this value, or even a scope we need to query for it.
             */
            // $table->string('source_type')
            //     ->nullable()
            //     ->default(null)
            //     ->after('api_url');

            // PHP Backed Enum: 'pending' | 'processing' | 'completed' | 'failed'
            /**
             * Oh Claude, if we're using a backed enum, don't make the default a literal.
             */
            $table->string('status')
                ->default(ImportStatus::Pending)
                ->after('api_url');

            // Populated after processing completes.
            /**
             * We don't need this because we already have total_rows, imported_rows, processed_rows (imported & processed),
             * and failed_rows.
             */
            // $table->unsignedInteger('record_count')
            //     ->nullable()
            //     ->default(null)
            //     ->after('status');

            /**
             * singular name seem stupids
             * How about just errors
             */
            // $table->text('error_message')
            $table->text('errors')
                ->nullable()
                ->default(null)
                ->after('delete_from_state');
        });
    }

    public function down(): void
    {
        Schema::table('imports', function (Blueprint $table) {
            $table->dropColumn([
                'api_url',
                // 'source_type',
                'status',
                // 'record_count',
                // 'error_message',
                'errors',
            ]);
        });
    }
};
