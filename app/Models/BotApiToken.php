<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Bearer token for the social-media automation app (Bot API). Only the SHA-256 hash
 * is stored; the plain token is shown to the admin once, at creation.
 */
class BotApiToken extends Model
{
    public const PREFIX = 'mgb_';

    /** ability => admin label */
    public const ABILITIES = [
        'products:read' => 'পণ্য, দাম ও স্টক দেখা',
        'catalog:read'  => 'বেস্ট সেলার / নতুন পণ্য / কম্বো (ডেইলি পোস্টের জন্য)',
        'orders:read'   => 'ফোন নম্বর দিয়ে অর্ডার স্ট্যাটাস দেখা',
        'leads:write'   => 'Messenger / কমেন্ট থেকে লিড জমা দেওয়া',
    ];

    protected $fillable = ['name', 'abilities', 'created_by'];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'abilities'    => 'array',
        'last_used_at' => 'datetime',
        'revoked_at'   => 'datetime',
    ];

    /** @return array{0: static, 1: string} the model and the plain token (show once). */
    public static function issue(string $name, array $abilities, ?int $userId = null): array
    {
        $plain = self::PREFIX.Str::random(48);
        $token = new static(['name' => $name, 'abilities' => array_values(array_intersect($abilities, array_keys(self::ABILITIES))), 'created_by' => $userId]);
        $token->token_hash = hash('sha256', $plain);
        $token->save();

        return [$token, $plain];
    }

    public static function findActive(?string $plain): ?static
    {
        if (! is_string($plain) || ! str_starts_with($plain, self::PREFIX) || strlen($plain) > 100) {
            return null;
        }
        return static::where('token_hash', hash('sha256', $plain))->whereNull('revoked_at')->first();
    }

    public function allows(string $ability): bool
    {
        return in_array($ability, (array) $this->abilities, true);
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(BotLead::class);
    }
}
