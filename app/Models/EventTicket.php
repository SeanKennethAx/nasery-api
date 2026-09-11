<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class EventTicket extends Model
{
    protected $fillable = [
        'event_id',
        'event_ticket_type_id',
        'attendee_name',
        'attendee_email',
        'qr_token',
        'source',
        'attendee_category',
        'payment_status',
        'status',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'checked_in_at' =>
        'datetime',

        'checked_in_by' =>
        'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(
            Event::class,
            'event_id'
        );
    }
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(
            EventTicketType::class,
            'event_ticket_type_id'
        );
    }
    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'checked_in_by'
        );
    }

    public function qrCheckIns(): HasMany
    {
        return $this->hasMany(
            EventQRCheckIn::class,
            'event_ticket_id'
        );
    }

    public function qrTicket(): HasOne
    {
        return $this->hasOne(
            QrTicket::class,
            'qr_token',
            'qr_token'
        );
    }
    public function registration(): HasOneThrough
    {
        return $this->hasOneThrough(
            EventRegistration::class,
            QrTicket::class,

            'qr_token',

            'registration_id',

            'qr_token',

            'registration_id'
        );
    }

    public function getTicketCodeAttribute(): string
    {
        return 'TKT-' .
            str_pad(
                (string) $this->id,
                6,
                '0',
                STR_PAD_LEFT
            );
    }

    public function getQrValueAttribute(): string
    {
        return 'NASERY:TICKET:' .
            $this->qr_token;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->checked_in_at) {
            return 'checked_in';
        }

        if ($this->status === 'cancelled') {
            return 'cancelled';
        }

        return 'registered';
    }

    public function isCheckedIn(): bool
    {
        return $this->checked_in_at !== null;
    }

    public function isCancelled(): bool
    {
        return $this->status ===
            'cancelled';
    }

    public function isValid(): bool
    {
        return (
            !$this->isCancelled() &&
            !$this->isCheckedIn()
        );
    }
}
