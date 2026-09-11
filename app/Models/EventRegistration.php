<?php

namespace App\Models;

use App\Models\QrTicket;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventRegistration extends Model
{
    protected $primaryKey =
    'registration_id';

    protected $fillable = [
        'event_id',
        'attendee_id',
        'event_ticket_type_id',
        'source',
        'attendee_category',
        'status',
        'payment_status',
        'registered_at',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'registered_at' =>
        'datetime',

        'checked_in_at' =>
        'datetime',
    ];

    public function attendee(): BelongsTo
    {
        return $this->belongsTo(
            Attendee::class,
            'attendee_id',
            'attendee_id'
        );
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class
        );
    }

    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(
            EventTicketType::class,
            'event_ticket_type_id'
        );
    }

    public function qrTicket(): HasOne
    {
        return $this->hasOne(
            QrTicket::class,
            'registration_id',
            'registration_id'
        );
    }
}
