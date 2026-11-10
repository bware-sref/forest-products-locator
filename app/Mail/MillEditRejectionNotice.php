<?php

namespace App\Mail;

use App\Jobs\Concerns\SendsMailFromMultipleEnvironments;
use App\Models\MillEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MillEditRejectionNotice extends Mailable
{
    use Queueable, SerializesModels;
    use SendsMailFromMultipleEnvironments;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public MillEdit $millEdit
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $addy = new Address(config('mail.from.address'), config('mail.from.name'));

        $subject = $this->applyEnvironmentPrefix("Your mill submission (#{$this->millEdit->id}) has been rejected");

        return new Envelope(
            from: $addy,
            subject: $subject,
            replyTo: [
                $addy,
            ]
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        /**
         * Finding the mill name is now a MillEdit model method.
         */
        $millName = $this->millEdit->millName();

        return new Content(
            markdown: 'mail.mill-edit-rejection-notice',
            with: [
                'mill_name' => $millName,
                'reason' => $this->millEdit->rejection_reason,
                'reviewed_at' => $this->millEdit->reviewed_at,
                'email' => $this->millEdit->submitter_email,
                'ip' => $this->millEdit->submitter_ip,
                'now' => now(),
                // 'url' => route('mills.show', ['mill' => $this->millEdit->mill_id]),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
