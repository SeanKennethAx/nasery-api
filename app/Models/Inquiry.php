<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'client_id',
        'event_title',
        'event_type',
        'event_date',
        'location',
        'expected_guests',
        'budget_range',
        'additional_details',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'expected_guests' => 'integer',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            ClientProfile::class,
            'client_id'
        );
    }
}
