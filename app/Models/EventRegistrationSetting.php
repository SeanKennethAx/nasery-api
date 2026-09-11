<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistrationSetting extends Model
{
    protected $fillable = [
        'event_id',
        'public_registration',
        'public_registration_token',
        'require_approval',
        'waitlist_enabled',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'public_registration' => 'boolean',
        'require_approval' => 'boolean',
        'waitlist_enabled' => 'boolean',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
