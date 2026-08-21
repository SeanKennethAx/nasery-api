<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'user_id',
    'company_name',
    'years_experience',
    'location',
    'bio',
    'tags',
    'specialties',
    'website',
    'facebook',
    'instagram',
    'banner_color',
])]
class Organizer extends Model
{
    protected $table = 'organizers';

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'specialties' => 'array',
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
}
