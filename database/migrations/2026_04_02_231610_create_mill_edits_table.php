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
        Schema::create('mill_edits', function (Blueprint $table) {
            $table->id();
            /**
             * Columns from FPL/N export
             * Resist the urge (for now at least) to break addresses out into a separate table.
             * Allow every column except match_id to be nullable and default to null.
             */
            $table->foreignId('mill_id')
                ->constrained()
                ->cascadeOnDelete();

            // submitter_email is just going to contain the submitter's validated email
            $table->string('submitter_email');
            $table->ipAddress('submitter_ip');

            // query string parameters for the Approve and Reject links in the emails sent to state agents
            $table->string('approve_hash');
            $table->string('reject_hash');

            // this might be a FK to the StateAgents table which doesn't exist yet
            // state agents will be assigned based on the Mill's state and possibly county
            // $table->foreignId('reviewed_by')->nullable()->constrained('users');

            $table->json('proposed_changes'); // the diff
            
            $table->enum('status', PublicationStatus::cases())
                ->default(PublicationStatus::Pending); // pending, approved, rejected
            
            // I don't think we need rejection reason because that would complicate the workflow
            // $table->text('rejection_reason')
            //     ->nullable();
            $table->timestamp('reviewed_at')
                ->nullable();
            $table->timestamps();

            $table->index(['mill_id', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mill_edits');
    }
};
