<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Event extends Model
{
    protected $fillable = [
        'inquiry_id',
        'quotation_id',
        'organizer_id',
        'client_id',
        'name',
        'event_type',
        'description',
        'event_date',
        'expected_guests',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'event_date' => 'date',
        'expected_guests' => 'integer',
    ];

    protected $appends = [
        'location',
        'public_registration',
        'public_registration_token',
        'require_approval',
        'waitlist_enabled',
        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    public function getLocationAttribute(): ?string
    {
        return $this->eventLocation?->venue_address
            ?? $this->eventLocation?->venue_name;
    }

    public function getPublicRegistrationAttribute(): bool
    {
        return (bool) ($this->registrationSettings?->public_registration ?? false);
    }

    public function getPublicRegistrationTokenAttribute(): ?string
    {
        return $this->registrationSettings?->public_registration_token;
    }

    public function getRequireApprovalAttribute(): bool
    {
        return (bool) ($this->registrationSettings?->require_approval ?? false);
    }

    public function getWaitlistEnabledAttribute(): bool
    {
        return (bool) ($this->registrationSettings?->waitlist_enabled ?? false);
    }

    public function getContactNameAttribute(): ?string
    {
        return $this->registrationSettings?->contact_name;
    }

    public function getContactEmailAttribute(): ?string
    {
        return $this->registrationSettings?->contact_email;
    }

    public function getContactPhoneAttribute(): ?string
    {
        return $this->registrationSettings?->contact_phone;
    }

    public function scopeManagedBy(Builder $query, User $user): Builder
    {
        return $query->whereHas(
            'organizer',
            fn (Builder $organizerQuery) =>
                $organizerQuery->where('user_id', $user->id)
        );
    }

    public function isManagedBy(User $user): bool
    {
        return $this->organizer()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(
            Inquiry::class,
            'inquiry_id'
        );
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(
            Quotation::class,
            'quotation_id'
        );
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(
            Organizer::class,
            'organizer_id'
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(
            ClientProfile::class,
            'client_id'
        );
    }

    public function eventLocation(): HasOne
    {
        return $this->hasOne(
            EventLocation::class,
            'event_id'
        );
    }

    public function registrationSettings(): HasOne
    {
        return $this->hasOne(
            EventRegistrationSetting::class,
            'event_id'
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            OrganizerReview::class,
            'event_id'
        );
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(
            EventTicketType::class,
            'event_id'
        );
    }

    public function preparationItems(): HasMany
    {
        return $this->hasMany(
            EventPreparationItem::class,
            'event_id'
        );
    }

    public function activities(): HasMany
    {
        return $this->hasMany(
            EventActivity::class,
            'event_id'
        );
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(
            EventTicket::class,
            'event_id'
        );
    }

    public function walkIns(): HasMany
    {
        return $this->hasMany(
            EventWalkIn::class,
            'event_id'
        );
    }

    public function qrCheckIns(): HasMany
    {
        return $this->hasMany(
            EventQRCheckIn::class,
            'event_id'
        );
    }
}
