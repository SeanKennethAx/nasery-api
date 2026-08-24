<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'email',
        'phone',
        'address',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizer(): HasOne
    {
        return $this->hasOne(Organizer::class, 'user_id');
    }

    public function client(): HasOne
    {
        return $this->hasOne(ClientProfile::class, 'user_id');
    }

    public function inquiries(): HasManyThrough
    {
        return $this->hasManyThrough(
            Inquiry::class,
            ClientProfile::class,
            'user_id',
            'client_id',
            'id',
            'id'
        );
    }
}
