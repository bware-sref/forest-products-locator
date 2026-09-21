<?php

namespace App\Mail;

use App\Enums\Environment;
use App\Models\MillEdit;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MillAddNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        protected MillEdit $millEdit
    )
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $addy = new Address(config('mail.from.address'), config('mail.from.name'));
        $subject = "New Mill Submitted (#{$this->millEdit->id})";
        if (Environment::Production->value !== config('app.env')) {
            $subject = config('mail.test.subject.prefix').$subject;
        }
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
         * I don't know if we need to use prepareSubmitted().
         * I actually expect to choke and die if we invoke it on a MillEdit without a Mill...
         * Yep, that's exactly what happened.
         * It's since been made to work without a mill.
         */
        // $submission = $this->millEdit->prepareSubmitted();

        $changes = $this->millEdit->getChanges();

        return new Content(
            markdown: 'mail.mill_add_notification',
            with: [
                'mill_name' => $changes['mill_name'] ?? 'unknown?!?',
                'created_at' => $this->millEdit->created_at,
                'email' => $this->millEdit->submitter_email,
                'ip' => $this->millEdit->submitter_ip,
                'now' => now(),
                'url' => route('mill-edits.show', ['mill_edit' => $this->millEdit]),
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
