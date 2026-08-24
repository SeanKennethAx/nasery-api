<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTicketType extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'price',
        'capacity',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'capacity' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }
}
