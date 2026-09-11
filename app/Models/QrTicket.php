<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrTicket extends Model
{
    protected $primaryKey =
    'qr_ticket_id';

    protected $fillable = [
        'registration_id',
        'qr_token',
        'qr_value',
        'generated_at',
        'emailed_at',
        'downloaded_at',
    ];

    protected $casts = [
        'generated_at' =>
        'datetime',

        'emailed_at' =>
        'datetime',

        'downloaded_at' =>
        'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(
            EventRegistration::class,
            'registration_id',
            'registration_id'
        );
    }
}
