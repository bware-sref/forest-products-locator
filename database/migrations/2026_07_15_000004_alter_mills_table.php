<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            // Points to the mill_raw_imports row that most recently
            // produced or updated this mill record. Null for mills
            // entered manually or via user submission.
            $table->foreignId('mill_raw_import_id')
                ->nullable()
                ->default(null)
                ->after('import_id')
                /**
                 * in case we migrate to PostgreSQL
                 */
                ->index()
                ->constrained('mill_raw_imports')
                ->nullOnDelete();

            // Contact person — supplied by FL, GA, SC, OK and possibly others.
            $table->string('contact_name', 255)
                ->nullable()
                ->default(null)
                ->after('web_site');

            $table->string('contact_title', 255)
                ->nullable()
                ->default(null)
                ->after('contact_name');

            // Secondary contact details — FL occasionally packs multiple
            // values into a single field; split on import.
            $table->string('telephone_2', 50)
                ->nullable()
                ->default(null)
                ->after('telephone');

            $table->string('email_2', 255)
                ->nullable()
                ->default(null)
                ->after('email');

            // Internal flag for records that warrant a closer look.
            // Not surfaced to state agents; used by admins only.
            $table->boolean('needs_review')
                ->default(false)
                ->after('email_2');

            // Optional catch-all for per-state fields that don't warrant
            // dedicated columns. No application logic depends on this column.
            $table->json('extended_attributes')
                ->nullable()
                ->default(null)
                ->after('needs_review');
        });
    }

    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            $table->dropForeign(['mill_raw_import_id']);
            $table->dropColumn([
                'mill_raw_import_id',
                'contact_name',
                'contact_title',
                'telephone_2',
                'email_2',
                'needs_review',
                'extended_attributes',
            ]);
        });
    }
};
