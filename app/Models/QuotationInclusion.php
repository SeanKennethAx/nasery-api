<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuotationInclusion extends Model
{
    protected $fillable = [
        'quotation_id',
        'description',
    ];

    public function quotation()
    {
        return $this->belongsTo(
            Quotation::class,
            'quotation_id'
        );
    }
}
