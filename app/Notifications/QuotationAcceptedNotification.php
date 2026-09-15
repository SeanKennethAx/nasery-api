<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationAcceptedNotification extends Notification
{
    use Queueable;

    public function __construct(protected Quotation $quotation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->quotation->loadMissing('inquiry');
        $inquiry = $this->quotation->inquiry;

        return [
            'type' => 'quotation_accepted',
            'quotation_id' => $this->quotation->id,
            'inquiry_id' => $inquiry?->id,
            'event_title' => $inquiry?->event_title
                ?? $inquiry?->event_type
                ?? 'Event',
            'quotation_amount' => $this->quotation->quotation_amount,
            'message' => 'Your quotation for '
                .($inquiry?->event_title ?? $inquiry?->event_type ?? 'an event')
                .' was accepted.',
        ];
    }
}
