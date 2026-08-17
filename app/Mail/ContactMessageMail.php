<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $senderName,
        public string $senderEmail,
        public string $messageSubject,
        public string $messageBody,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contact form: {$this->messageSubject}",
            replyTo: [$this->senderEmail],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-message',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
