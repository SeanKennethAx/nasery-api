<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPayment extends Model
{
    protected $fillable = [
        'reference',
        'event_id',
        'checkout_session_id',
        'amount',
        'currency',
        'status',
        'event_ticket_ids',
        'provider_payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'event_ticket_ids' => 'array',
        'provider_payload' => 'array',
        'paid_at' => 'datetime',
    ];
}
