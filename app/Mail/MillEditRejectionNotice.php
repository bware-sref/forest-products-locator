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

        return new Envelope(
            from: $addy,
            subject: $this->resolveSubject(applyEnvironmentPrefix: true),
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
        return new Content(
            markdown: 'mail.mill-edit-rejection-notice',
            with: [
                /**
                 * Finding the mill name is now a MillEdit model 
                 * method.
                 */
                'mill_name' => $this->millEdit->millName(),
                'isNewMill' => $this->millEdit->isNewMill(),
                'subject' => $this->resolveSubject(applyEnvironmentPrefix: false),
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

    protected function resolveSubject(bool $applyEnvironmentPrefix = true): string
    {
        $millName = $this->millEdit->millName();
        $subject = $this->millEdit->isNewMill() ?
            "Your new mill submission for \"{$millName}\" has been rejected."
            :
            "Your edits for \"{$millName}\" have been rejected.";
        return $applyEnvironmentPrefix ?
            $this->applyEnvironmentPrefix($subject)
            :
            $subject;
    }
}
