<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventPreparationItem extends Model
{
    protected $fillable = [
        'event_id',
        'label',
        'is_completed',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }
}
