<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventActivity extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'description',
        'category',
        'tag',
        'person',
        'status',
        'activity_at',
    ];

    protected $casts = [
        'activity_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }
}
