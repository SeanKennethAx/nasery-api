<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
])]
class Organizer extends Model
{
    protected $table = 'organizers';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
