<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventWalkIn extends Model
{
    protected $fillable = [
        'event_id',
        'event_ticket_type_id',
        'name',
        'email',
        'phone',
        'amount',
        'payment_status',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
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
}
