<?php

namespace App\Support;

use App\Models\Order;
use App\Models\WebsiteSetting;

/**
 * Admin-editable wholesale quote policy (Admin → Commission settings):
 * first N wholesale orders may pay COD, after that an advance is required;
 * plus the standard wording for "delivery charge applies" and the COD rule.
 */
class WholesalePolicy
{
    public const DEFAULTS = [
        'wholesale_cod_order_limit'    => '2',
        'wholesale_default_advance'    => '30',
        'wholesale_delivery_later_text' => 'ডেলিভারি চার্জ প্রযোজ্য — লোকেশন ও ওজন অনুযায়ী অর্ডার কনফার্মের সময় জানানো হবে।',
        'wholesale_cod_policy_text'    => 'নতুন পাইকারি ক্রেতাদের প্রথম {n}টি অর্ডার ক্যাশ অন ডেলিভারি (COD)। এরপর থেকে অর্ডার কনফার্ম করতে অগ্রিম পেমেন্ট প্রয়োজন।',
    ];

    public static function get(string $key): string
    {
        $value = WebsiteSetting::get($key, self::DEFAULTS[$key] ?? '');

        return $value === '' ? (self::DEFAULTS[$key] ?? '') : $value;
    }

    public static function codOrderLimit(): int
    {
        return max(0, (int) self::get('wholesale_cod_order_limit'));
    }

    public static function defaultAdvance(): float
    {
        return min(100, max(0, (float) self::get('wholesale_default_advance')));
    }

    public static function deliveryLaterText(): string
    {
        return self::get('wholesale_delivery_later_text');
    }

    public static function codPolicyText(): string
    {
        return str_replace('{n}', (string) self::codOrderLimit(), self::get('wholesale_cod_policy_text'));
    }

    /** Wholesale orders (from enquiries) this customer already placed, cancelled ones excluded. */
    public static function previousOrders(?int $customerId): int
    {
        if (! $customerId) {
            return 0;
        }

        return Order::where('customer_id', $customerId)
            ->whereNotNull('enquiry_id')
            ->whereNotIn('order_status', ['cancelled', 'canceled'])
            ->count();
    }

    /** Still inside the COD allowance? */
    public static function codEligible(?int $customerId): bool
    {
        return self::previousOrders($customerId) < self::codOrderLimit();
    }
}
