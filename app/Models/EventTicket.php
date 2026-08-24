<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    protected $fillable = [
        'event_id',
        'event_ticket_type_id',
        'attendee_name',
        'attendee_email',
        'qr_token',
        'source',
        'payment_status',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }

    public function ticketType()
    {
        return $this->belongsTo(
            EventTicketType::class,
            'event_ticket_type_id'
        );
    }

    public function checkedInBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }

    public function qrCheckIns()
    {
        return $this->hasMany(
            EventQRCheckIn::class,
            'event_ticket_id'
        );
    }
}
