<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Inquiry extends Model
{
    protected $table = 'inquiries';

    protected $fillable = [
        'client_id',
        'event_title',
        'event_type',
        'event_date',
        'location',
        'expected_guests',
        'budget_range',
        'additional_details',
        'status',
        'awarded_quotation_id',
    ];

    protected $casts = [
        'client_id' => 'integer',
        'event_date' => 'date',
        'expected_guests' => 'integer',
        'awarded_quotation_id' => 'integer',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            ClientProfile::class,
            'client_id'
        );
    }

    public function awardedQuotation(): BelongsTo
    {
        return $this->belongsTo(
            Quotation::class,
            'awarded_quotation_id'
        );
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(
            Quotation::class,
            'inquiry_id'
        );
    }
    public function event(): HasOne
    {
        return $this->hasOne(
            Event::class,
            'inquiry_id'
        );
    }
}
