<?php

namespace App\Mail;

use App\Models\EventTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EventTicketMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public EventTicket $ticket,
        public string $pdfContent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your NaSeRy Event Ticket - ' .
                $this->ticket->event->name
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.event-ticket'
        );
    }

    public function attachments(): array
    {
        $ticketId =
            'TKT-' .
            str_pad(
                (string) $this->ticket->id,
                6,
                '0',
                STR_PAD_LEFT
            );

        return [
            Attachment::fromData(
                fn() => $this->pdfContent,
                $ticketId . '.pdf'
            )->withMime(
                'application/pdf'
            ),
        ];
    }
}
