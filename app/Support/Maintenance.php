<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

/** Reads the admin-controlled maintenance settings (WebsiteSetting keys). */
class Maintenance
{
    public const TIMEZONE = 'Asia/Dhaka';

    public const KEYS = [
        'maintenance_enabled', 'maintenance_mode', 'maintenance_title', 'maintenance_message',
        'maintenance_until', 'maintenance_contact', 'maintenance_block_orders',
        'maintenance_block_vendors', 'maintenance_allowed_ips',
    ];

    public const DEFAULT_TITLE = 'ওয়েবসাইটের কাজ চলছে';
    public const DEFAULT_MESSAGE = 'আমরা ওয়েবসাইটটি আরও ভালো করতে কাজ করছি। কিছুক্ষণ পর আবার চেষ্টা করুন।';

    public static function enabled(): bool
    {
        return WebsiteSetting::get('maintenance_enabled', '0') === '1';
    }

    public static function mode(): string
    {
        return WebsiteSetting::get('maintenance_mode', 'full') === 'banner' ? 'banner' : 'full';
    }

    public static function isFull(): bool
    {
        return self::enabled() && self::mode() === 'full';
    }

    public static function isBanner(): bool
    {
        return self::enabled() && self::mode() === 'banner';
    }

    public static function blocksOrders(): bool
    {
        return self::isBanner() && WebsiteSetting::get('maintenance_block_orders', '0') === '1';
    }

    public static function blocksVendors(): bool
    {
        return self::enabled() && WebsiteSetting::get('maintenance_block_vendors', '0') === '1';
    }

    public static function title(): string
    {
        return trim(WebsiteSetting::get('maintenance_title')) ?: self::DEFAULT_TITLE;
    }

    public static function message(): string
    {
        return trim(WebsiteSetting::get('maintenance_message')) ?: self::DEFAULT_MESSAGE;
    }

    public static function contact(): string
    {
        return trim(WebsiteSetting::get('maintenance_contact'));
    }

    /** Stored as 'Y-m-d H:i' in Asia/Dhaka (the app itself runs in UTC). */
    public static function until(): ?CarbonImmutable
    {
        $value = trim(WebsiteSetting::get('maintenance_until'));
        if ($value === '') {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, self::TIMEZONE);
        } catch (\Throwable) {
            return null;
        }
    }

    /** End time has passed. Maintenance stays on (never auto-disabled); only the countdown hides. */
    public static function expired(): bool
    {
        $until = self::until();
        return $until !== null && $until->isPast();
    }

    /** Seconds for the Retry-After header: time left until the end, else one hour. */
    public static function retryAfter(): int
    {
        $until = self::until();
        return $until && $until->isFuture() ? max(60, (int) now()->diffInSeconds($until)) : 3600;
    }

    public static function allowedIps(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', WebsiteSetting::get('maintenance_allowed_ips')))));
    }

    /**
     * $request->ip() honours trustProxies (Cloudflare tunnel → real client IP).
     * A convenience bypass for staff, not a security boundary.
     */
    public static function ipAllowed(Request $request): bool
    {
        return in_array($request->ip(), self::allowedIps(), true);
    }

    /** tel: / wa.me links for the contact number (digits only; BD local numbers get 88). */
    public static function contactLinks(): array
    {
        $digits = preg_replace('/\D+/', '', self::contact());
        if ($digits === '') {
            return [];
        }
        $wa = str_starts_with($digits, '0') ? '88'.$digits : $digits;
        return ['tel' => 'tel:+'.ltrim($wa, '+'), 'whatsapp' => 'https://wa.me/'.$wa];
    }
}
