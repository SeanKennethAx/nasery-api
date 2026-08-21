<?php

namespace App\Notifications;

use App\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotationReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Quotation $quotation
    ) {}

    public function via(object $notifiable): array
    {
        return [
            'database',
        ];
    }

    public function toArray(object $notifiable): array
    {
        $quotation = $this->quotation;

        $quotation->loadMissing([
            'inquiry',
            'organizer.user',
        ]);

        $inquiry = $quotation->inquiry;
        $organizer = $quotation->organizer;
        $organizerUser = $organizer?->user;

        $organizerName = trim(
            collect([
                $organizerUser?->firstname,
                $organizerUser?->middlename,
                $organizerUser?->lastname,
            ])
                ->filter()
                ->join(' ')
        );

        return [
            'type' => 'quotation_received',

            'quotation_id' => $quotation->id,

            'inquiry_id' => $inquiry?->id,

            'event_title' =>
            $inquiry?->event_title
                ?? $inquiry?->event_type
                ?? 'Event',

            'organizer_id' => $organizer?->id,

            'organizer_name' =>
            $organizerName ?: 'An organizer',

            'quotation_amount' =>
            $quotation->quotation_amount,

            'message' => ($organizerName ?: 'An organizer')
                . ' submitted a quotation for your event.',
        ];
    }
}
