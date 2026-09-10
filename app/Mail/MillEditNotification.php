<?php

namespace App\Mail;

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
        return new Envelope(
            from: $addy,
            subject: 'New Mill Edit Submitted',
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
        $submitted = $this->millEdit->mill->replicate();
        $submitted->fill($this->millEdit->getChanges());

        return new Content(
            markdown: 'mail.mill_edit_notification',
            with: [
                'mill_name' => $this->millEdit->mill->mill_name,
                'original' => $this->millEdit->originalMill(),
                'submission' => $this->millEdit->prepareSubmitted(),
                'email' => $this->millEdit->submitter_email,
                'ip' => $this->millEdit->submitter_ip,
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
