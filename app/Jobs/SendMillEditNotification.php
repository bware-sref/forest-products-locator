<?php

namespace App\Jobs;

use App\Jobs\Concerns\ResolvesReviewRecipients;
use App\Mail\MillEditNotification;
use App\Models\MillEdit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMillEditNotification implements ShouldQueue
{
    use Queueable;
    use ResolvesReviewRecipients;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MillEdit $millEdit
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // who gets it: see ResolvesReviewRecipients
        // store toWhom so we can update the record accordingly
        $toWhom = $this->resolveToWhom();
        $sent = Mail::to($toWhom)
            ->send(new MillEditNotification($this->millEdit));

        if (! $sent) {
            Log::debug("\n".self::class.":\nFailed to send MillEditNotification for MillEdit #{$this->millEdit->id}. Releasing job to attempt resending later.");
            $this->release(10);
            return;
        }

        /**
         * If sent, update the record
         */
        $this->millEdit->update([
            'sent' => true,
            'sent_to' => Arr::flatten($toWhom), // probably need to cast this to a string
        ]);
    }

    protected function resolveToWhom(): array
    {
        return $this->reviewRecipientsFor($this->millEdit->mill?->state);
    }
}
