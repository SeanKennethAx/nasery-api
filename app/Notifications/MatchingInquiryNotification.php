<?php

namespace App\Notifications;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MatchingInquiryNotification extends Notification
{
    use Queueable;

    public function __construct(protected Inquiry $inquiry) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'matching_inquiry',
            'inquiry_id' => $this->inquiry->id,
            'event_title' => $this->inquiry->event_title
                ?? $this->inquiry->event_type
                ?? 'New event inquiry',
            'event_type' => $this->inquiry->event_type,
            'event_date' => $this->inquiry->event_date,
            'message' => 'A new '.$this->inquiry->event_type
                .' inquiry matches your services.',
        ];
    }
}
