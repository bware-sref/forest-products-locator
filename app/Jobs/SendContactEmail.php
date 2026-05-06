<?php

namespace App\Jobs;

use App\Mail\ContactEmail;
use App\Models\Contact;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Queue\Queueable;

class SendContactEmail implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Contact $contact
    )
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        //
        $sent = Mail::to(config('mail.from.address'))
            ->send(new ContactEmail($this->contact));

        // if we didn't send it, release the job to try again later
        if (! $sent) {
            Log::debug(sprintf('Failed to send email for Contact %d. Releasing job to attempt resending later.', $this->contact->id));
            $this->release(10);
            return;
        }

        // if we did send it, update the record
        $this->contact->sent = true;
        $this->contact->save();
    }
}
