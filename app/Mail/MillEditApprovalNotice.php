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

class MillEditApprovalNotice extends Mailable
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
        /**
         * Finding the mill name is now a MillEdit model method.
         */
        $with = [
            'mill_name' => $this->millEdit->millName(),
            'isNewMill' => $this->millEdit->isNewMill(),
            'subject' => $this->resolveSubject(applyEnvironmentPrefix: false),
            'reviewed_at' => $this->millEdit->reviewed_at,
            'email' => $this->millEdit->submitter_email,
            'ip' => $this->millEdit->submitter_ip,
            'now' => now(),
        ];

        if ($this->millEdit->mill) {
            $with['url'] = route('mills.show', ['mill' => $this->millEdit->mill]);
        }

        return new Content(
            markdown: 'mail.mill-edit-approval-notice',
            with: $with
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

    /**
     * differentiate messaging depending on whether this is a 
     * new mill submission or an edit submission
     */
    protected function resolveSubject(bool $applyEnvironmentPrefix = true): string
    {
        $millName = $this->millEdit->millName();
        $subject = $this->millEdit->isNewMill() ?
            "Your new mill submission for \"{$millName}\" has been approved."
            :
            "Your edits for \"{$millName}\" have been approved.";
        return $applyEnvironmentPrefix ?
            $this->applyEnvironmentPrefix($subject)
            :
            $subject;
    }
}
