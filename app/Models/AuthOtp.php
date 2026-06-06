<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuthOtp extends Model
{
    protected $fillable = [
        'identifier', 'identifier_type', 'channel', 'otp_hash',
        'purpose', 'user_type', 'expires_at', 'verified_at',
        'attempts', 'max_attempts', 'ip_address', 'user_agent',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'verified_at'  => 'datetime',
        'attempts'     => 'integer',
        'max_attempts' => 'integer',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isLocked(): bool
    {
        return $this->attempts >= $this->max_attempts;
    }

    /** The newest unverified code issued for this identifier + purpose. */
    public function scopeLatestFor(Builder $query, string $identifier, string $purpose): Builder
    {
        return $query->where('identifier', $identifier)
            ->where('purpose', $purpose)
            ->whereNull('verified_at')
            ->latest();
    }
}
