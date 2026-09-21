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

class MillEditNotification extends Mailable
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
        $subject = "Mill Edit Submitted (#{$this->millEdit->id})";
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
         * These are not used...
         */
        // $submitted = $this->millEdit->mill->replicate();
        // $submitted->fill($this->millEdit->getChanges());

        /**
         * Showing the diff in a Markdown email might be tricky...
         * My initial attempt to loop over data in the Blade caused fatal exceptions, so another approach is needed.
         * We could try to build a string for a Markdown table and pass it to the email
         */
        $original = $this->millEdit->originalMill();
        $submission = $this->millEdit->prepareSubmitted();

        return new Content(
            markdown: 'mail.mill_edit_notification',
            with: [
                'mill_name' => $this->millEdit->mill->mill_name,
                'original' => $original, // $this->millEdit->originalMill(),
                'submission' => $submission, // $this->millEdit->prepareSubmitted(),
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
