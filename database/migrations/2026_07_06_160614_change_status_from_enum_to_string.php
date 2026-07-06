<?php

use App\Enums\PublicationStatus;
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
            $table->string('status')
                ->default(PublicationStatus::Pending)
                ->change();
        });

        Schema::table('state_resources', function (Blueprint $table) {
            $table->string('status')
                ->default(PublicationStatus::Pending)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mill_edits', function (Blueprint $table) {
            // and revert-ish
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Pending)
                ->change();
        });

        Schema::table('state_resources', function (Blueprint $table) {
            // more revert-ish
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Pending)
                ->change();
        });
    }
};
