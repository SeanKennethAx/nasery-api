<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmailVerificationCodeMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public string $code,
        public string $eventName,
        public string $purpose = 'event'
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your NaSeRy verification code'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-verification-code'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
