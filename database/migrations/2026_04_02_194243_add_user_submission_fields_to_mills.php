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
        Schema::table('mills', function (Blueprint $table) {
            // publication status
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Approved);

            // pretty sure submitted_by needs to be nullable since all existing records will have null
            // maybe not though?
            $table->string('submitter_email')
                ->nullable(true);
            $table->ipAddress('submitter_ip')
                ->nullable(true);

            // query string params for approve and reject
            $table->string('approve_hash')
                ->nullable(true);
            $table->string('reject_hash')
                ->nullable(true);                

            // timestamp when mill was approved or rejected
            $table->timestamp('reviewed_at')
                ->nullable(true);

            // add an index to status to make lookups faster
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mills', function (Blueprint $table) {
            // kill the index first
            // passing an array means we don't need to know the precise index name, only the column name that
            // the index is on
            $table->dropIndex(['status']);

            // now for the columns
            $table->dropColumn([
                'status',
                'submitter_email',
                'submitter_ip',
                'approve_hash',
                'reject_hash',
                'reviewed_at',
            ]);
        });
    }
};
