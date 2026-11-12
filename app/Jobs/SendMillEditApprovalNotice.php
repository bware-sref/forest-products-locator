<?php

namespace App\Jobs;

use App\Jobs\Concerns\ResolvesReviewRecipients;
use App\Mail\MillEditApprovalNotice;
use App\Models\MillEdit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMillEditApprovalNotice implements ShouldQueue
{
    use Queueable;
    use ResolvesReviewRecipients;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MillEdit $millEdit
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $toWhom = $this->resolveToWhom();
        $bcc = $this->resolveBcc();
        $sent = Mail::to($toWhom)
            ->bcc($bcc)
            ->send(new MillEditApprovalNotice($this->millEdit));

        if (!$sent) {
            Log::debug("\n" . self::class . ":\nFailed to send MillEditApprovalNotice for MillEdit #{$this->millEdit->id}. Releasing job to attempt resending later.");
            $this->release(10);
            return;
        }

        /**
         * If sent, update the record
         */
        $this->millEdit->update([
            'submitter_notified_at' => now(),
        ]);
    }

    protected function resolveToWhom(): string
    {
        return $this->millEdit->submitter_email;
    }

    protected function resolveBcc(): array
    {
        return $this->getSiteReviewers();
    }
}
