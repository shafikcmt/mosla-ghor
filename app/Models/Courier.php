<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Courier extends Model
{
    protected $fillable = [
        'name', 'slug', 'status', 'api_enabled', 'api_key', 'api_secret',
        'base_url', 'is_default', 'vendor_allowed', 'notes',
        'courier_api_last_checked_at', 'courier_api_last_status', 'courier_api_last_error',
        'courier_api_last_message',
    ];

    protected $casts = [
        'api_enabled'    => 'boolean',
        'is_default'     => 'boolean',
        'vendor_allowed' => 'boolean',
        'courier_api_last_checked_at' => 'datetime',
    ];

    /** Default Steadfast API base URL. */
    public const DEFAULT_STEADFAST_BASE_URL = 'https://portal.steadfast.com.bd/api/v1';

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

    /**
     * Clean the base_url whenever it is set so stored values never carry
     * whitespace, newlines, or hidden/zero-width characters that would break
     * DNS resolution (cause of "Could not resolve host" cURL error 6).
     */
    public function setBaseUrlAttribute($value): void
    {
        if ($value === null) {
            $this->attributes['base_url'] = null;
            return;
        }

        // Strip control chars, zero-width spaces, and BOM; then trim.
        $clean = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', (string) $value);
        $clean = trim((string) $clean);

        $this->attributes['base_url'] = $clean === '' ? null : $clean;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * May a vendor select this courier for their own parcels?
     * Active + flagged available to vendors.
     */
    public function isAvailableForVendor(): bool
    {
        return $this->isActive() && (bool) $this->vendor_allowed;
    }

    /**
     * Cleaned base URL (control chars / whitespace stripped), or the default
     * Steadfast endpoint when none is set. Credentials are never included.
     */
    public function normalizedBaseUrl(): string
    {
        $raw = (string) ($this->base_url ?? '');
        $clean = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $raw);
        $clean = trim((string) $clean);

        if ($clean === '') {
            return self::DEFAULT_STEADFAST_BASE_URL;
        }

        if (! preg_match('~^https?://~i', $clean)) {
            $clean = 'https://' . $clean;
        }

        return rtrim($clean, '/');
    }

    public static function vendorAllowed()
    {
        return static::where('status', 'active')->where('vendor_allowed', true);
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
