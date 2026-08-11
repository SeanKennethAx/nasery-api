<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
])]
class ClientProfile extends Model
{
    protected $table = 'clients';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
