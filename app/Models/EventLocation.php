<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventLocation extends Model
{
    protected $fillable = [
        'event_id',
        'venue_name',
        'venue_address',
        'google_place_id',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
