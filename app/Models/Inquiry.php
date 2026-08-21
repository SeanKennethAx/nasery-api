<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'status',
        'awarded_quotation_id',
    ];

    protected $casts = [
        'event_date' => 'date',
        'expected_guests' => 'integer',
    ];

    public function client()
    {
        return $this->belongsTo(
            ClientProfile::class,
            'client_id'
        );
    }

    public function quotations()
    {
        return $this->hasMany(
            Quotation::class,
            'inquiry_id'
        );
    }

    public function awardedQuotation()
    {
        return $this->belongsTo(
            Quotation::class,
            'awarded_quotation_id'
        );
    }
}
