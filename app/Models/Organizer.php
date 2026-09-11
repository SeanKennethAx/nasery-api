<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organizer extends Model
{
    protected $table = 'organizers';

    protected $fillable = [
        'user_id',
        'company_name',
        'years_experience',
        'location',
        'google_place_id',
        'latitude',
        'longitude',
        'service_radius_km',
        'bio',
        'tags',
        'specialties',
        'website',
        'facebook',
        'instagram',
        'banner_color',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'specialties' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
            'service_radius_km' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'user_id'
        );
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(
            Quotation::class,
            'organizer_id'
        );
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(
            OrganizerReview::class,
            'organizer_id'
        );
    }

    public function events(): HasMany
    {
        return $this->hasMany(
            Event::class,
            'organizer_id'
        );
    }

    public function teamMembers(): HasMany
    {
        return $this->hasMany(TeamMember::class, 'organizer_id');
    }
}
