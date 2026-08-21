<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $fillable = [
        'inquiry_id',
        'organizer_id',
        'quotation_amount',
        'package_name',
        'timeline',
        'quotation_details',
        'quotation_status',
    ];

    protected $casts = [
        'quotation_amount' => 'decimal:2',
    ];

    public function inquiry()
    {
        return $this->belongsTo(
            Inquiry::class,
            'inquiry_id'
        );
    }

    public function organizer()
    {
        return $this->belongsTo(
            Organizer::class,
            'organizer_id'
        );
    }

    public function inclusions()
    {
        return $this->hasMany(
            QuotationInclusion::class,
            'quotation_id'
        );
    }
}
