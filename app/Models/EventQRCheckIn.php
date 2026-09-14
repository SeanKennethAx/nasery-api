<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventQRCheckIn extends Model
{
    protected $table = 'event_qr_check_ins';

    protected $fillable = [
        'event_id',
        'event_ticket_id',
        'checked_in_by',
        'checked_in_at',
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

    public function ticket()
    {
        return $this->belongsTo(
            EventTicket::class,
            'event_ticket_id'
        );
    }

    public function checkedInBy()
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }
}
