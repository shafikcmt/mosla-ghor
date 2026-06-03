<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    protected $fillable = [
        'name', 'slug', 'status', 'api_enabled', 'api_key', 'api_secret',
        'base_url', 'is_default', 'notes',
        'courier_api_last_checked_at', 'courier_api_last_status', 'courier_api_last_error',
    ];

    protected $casts = [
        'api_enabled' => 'boolean',
        'is_default'  => 'boolean',
        'courier_api_last_checked_at' => 'datetime',
    ];

    /**
     * Never expose API credentials when the model is serialized (e.g. accidental
     * JSON responses or toArray() leaks reaching a vendor view).
     */
    protected $hidden = [
        'api_key', 'api_secret',
    ];

    /**
     * Slugs for which a real API integration exists in the codebase.
     * Other couriers are treated as manual booking even if api_enabled is on.
     */
    public const API_SUPPORTED_SLUGS = ['steadfast'];

    public function rates(): HasMany
    {
        return $this->hasMany(DeliveryRate::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public static function default(): ?self
    {
        return static::where('is_default', true)->where('status', 'active')->first();
    }

    public static function active()
    {
        return static::where('status', 'active');
    }

    /**
     * Does this courier have a working API integration in the codebase
     * AND is its API turned on with credentials present?
     */
    public function supportsApi(): bool
    {
        return in_array($this->slug, self::API_SUPPORTED_SLUGS, true);
    }

    /**
     * API can actually be used for sending: integration exists, toggle on,
     * credentials configured.
     */
    public function apiUsable(): bool
    {
        return $this->supportsApi()
            && $this->api_enabled
            && $this->isConfigured();
    }

    /**
     * Credentials present. Steadfast needs key + secret; others may only need a base_url.
     */
    public function isConfigured(): bool
    {
        if ($this->supportsApi()) {
            return ! empty($this->api_key) && ! empty($this->api_secret);
        }

        return ! empty($this->base_url);
    }

    /**
     * Masked preview of a stored credential, never the full value.
     */
    public function maskedKey(): string
    {
        return $this->mask($this->api_key);
    }

    public function maskedSecret(): string
    {
        return $this->mask($this->api_secret);
    }

    protected function mask(?string $value): string
    {
        if (empty($value)) {
            return '';
        }

        $len = strlen($value);
        if ($len <= 4) {
            return str_repeat('•', $len);
        }

        return str_repeat('•', max(4, $len - 4)) . substr($value, -4);
    }
}
