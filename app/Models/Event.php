<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'location',
        'expected_guests',

        'start_time',
        'end_time',
        'status',

        'public_registration',
        'require_approval',
        'waitlist_enabled',

        'contact_name',
        'contact_email',
        'contact_phone',
    ];

    protected $casts = [
        'event_date' => 'date',
        'expected_guests' => 'integer',

        'public_registration' => 'boolean',
        'require_approval' => 'boolean',
        'waitlist_enabled' => 'boolean',
    ];

    public function inquiry()
    {
        return $this->belongsTo(
            Inquiry::class,
            'inquiry_id'
        );
    }

    public function quotation()
    {
        return $this->belongsTo(
            Quotation::class,
            'quotation_id'
        );
    }

    public function organizer()
    {
        return $this->belongsTo(
            Organizer::class,
            'organizer_id'
        );
    }

    public function client()
    {
        return $this->belongsTo(
            ClientProfile::class,
            'client_id'
        );
    }

    public function ticketTypes()
    {
        return $this->hasMany(
            EventTicketType::class,
            'event_id'
        );
    }

    public function preparationItems()
    {
        return $this->hasMany(
            EventPreparationItem::class,
            'event_id'
        );
    }
    public function activities()
    {
        return $this->hasMany(
            EventActivity::class,
            'event_id'
        );
    }
    public function tickets()
    {
        return $this->hasMany(
            EventTicket::class,
            'event_id'
        );
    }
    public function walkIns()
    {
        return $this->hasMany(
            EventWalkIn::class,
            'event_id'
        );
    }
    public function qrCheckIns()
    {
        return $this->hasMany(
            EventQRCheckIn::class,
            'event_id'
        );
    }
}
