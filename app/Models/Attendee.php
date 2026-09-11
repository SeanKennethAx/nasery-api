<?php

namespace App\Models;

use App\Models\EventRegistration;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attendee extends Model
{
    protected $primaryKey =
    'attendee_id';

    protected $fillable = [
        'full_name',
        'email',
        'contact_no',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(
            EventRegistration::class,
            'attendee_id',
            'attendee_id'
        );
    }
}
