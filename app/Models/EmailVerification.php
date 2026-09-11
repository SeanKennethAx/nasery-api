<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailVerification extends Model
{
    protected $fillable = [
        'email',
        'event_token',
        'code_hash',
        'verification_token',
        'expires_at',
        'verified_at',
        'used_at',
        'last_sent_at',
        'attempts',
    ];

    protected $casts = [
        'expires_at' =>
        'datetime',

        'verified_at' =>
        'datetime',

        'used_at' =>
        'datetime',

        'last_sent_at' =>
        'datetime',

        'attempts' =>
        'integer',
    ];

    public function isExpired(): bool
    {
        return (
            $this->expires_at === null ||
            $this->expires_at->isPast()
        );
    }

    public function isVerified(): bool
    {
        return (
            $this->verified_at !== null &&
            filled(
                $this->verification_token
            )
        );
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function hasTooManyAttempts(): bool
    {
        return (
            (int) $this->attempts >= 5
        );
    }

    public function canBeVerified(): bool
    {
        return (
            !$this->isExpired() &&
            !$this->isUsed() &&
            !$this->hasTooManyAttempts()
        );
    }

    public function isAvailableForRegistration(): bool
    {
        return (
            $this->isVerified() &&
            !$this->isUsed()
        );
    }

    public function markAsVerified(
        string $verificationToken
    ): void {
        $this->update([
            'verified_at' =>
            now(),

            'verification_token' =>
            $verificationToken,

            'attempts' =>
            0,
        ]);
    }

    public function markAsUsed(): void
    {
        $this->update([
            'used_at' =>
            now(),
        ]);
    }

    public function recordFailedAttempt(): void
    {
        $this->increment(
            'attempts'
        );

        $this->refresh();
    }

    public function resetForNewCode(
        string $codeHash
    ): void {
        $this->update([
            'code_hash' =>
            $codeHash,

            'verification_token' =>
            null,

            'expires_at' =>
            now()->addMinutes(10),

            'verified_at' =>
            null,

            'used_at' =>
            null,

            'last_sent_at' =>
            now(),

            'attempts' =>
            0,
        ]);
    }
}
