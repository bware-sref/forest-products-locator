<?php

use App\Enums\MillSubmissionStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mill_submissions', function (Blueprint $table) {
            $table->id();

            // Null for new mill submissions; points to the mill being
            // edited for edit submissions.
            $table->foreignId('mill_id')
                ->nullable()
                ->default(null)
                /**
                 * In case we migrate to PostgreSQL
                 */
                ->index()
                ->constrained('mills')
                ->nullOnDelete();

            // Populated from the form; used to route to the correct
            // state agent for review. Required even for new mill submissions.
            $table->foreignId('state_id')
                /**
                 * In case we migrate to PostgreSQL
                 */
                ->index()
                ->constrained('states')
                ->restrictOnDelete();

            // PHP Backed Enum: 'new' | 'edit'
            /**
             * Type can be inferred from mill_id being null or not.
             */
            // $table->string('type');

            // Structured form payload. Shape varies between new and edit
            // submissions but is always well-formed JSON from the web form.
            $table->json('payload');

            $table->string('submitter_name', 255);
            $table->string('submitter_email', 255);

            // VARCHAR(45) accommodates both IPv4 and IPv6.
            $table->ipAddress('submitter_ip');

            // PHP Backed Enum: 'pending' | 'approved' | 'rejected' | 'failed'
            $table->string('status')
                // ->default('pending');
                ->default(MillSubmissionStatus::Pending)
                ->index();

            // The user (state agent or admin) who reviewed the submission.
            $table->foreignId('reviewed_by')
                ->nullable()
                ->default(null)
                /**
                 * In case we migrate to PostgreSQL
                 */
                ->index()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reviewed_at')
                ->nullable()
                ->default(null);

            // Reviewer notes — visible to admins; optionally to submitter.
            $table->text('notes')
                ->nullable()
                ->default(null);

            $table->timestamps();

            /**
             * replaced with chained ->index()
             */
            // $table->index(['state_id', 'status']);
            // $table->index(['mill_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mill_submissions');
    }
};
