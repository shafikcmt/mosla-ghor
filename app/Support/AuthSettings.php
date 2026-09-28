<?php

namespace App\Support;

use App\Models\WebsiteSetting;

/**
 * Typed accessors over the WebsiteSetting key/value store for every
 * admin-controlled auth + OTP toggle. Used by controllers AND blades so the
 * same gate is evaluated consistently everywhere. Mirrors {@see VendorSettings}.
 */
class AuthSettings
{
    /** key => string default. Drives the admin form + defaults. */
    public const BOOL_DEFAULTS = [
        'customer_password_login'    => '1',
        'customer_otp_login'         => '1',
        'vendor_password_login'      => '1',
        'vendor_otp_login'           => '1',
        'otp_sms_enabled'            => '0',
        'otp_whatsapp_enabled'       => '0',
        'otp_email_enabled'          => '1',
        'show_email_field_register'  => '0',
        'phone_verification_required' => '0',
        'vendor_approval_required'   => '1',
        'vendor_auto_approve'        => '0',
        // Guest (not logged in) permissions — default on = the long-standing behaviour.
        'guest_checkout_enabled'     => '1',
        'guest_enquiry_enabled'      => '1',
        'guest_review_enabled'       => '1',
    ];

    /** Features a guest may be allowed to use, and the Bangla prompt shown when not. */
    public const GUEST_FEATURES = [
        'checkout' => 'অর্ডার করতে লগইন করুন',
        'enquiry'  => 'Enquiry পাঠাতে লগইন করুন',
        'review'   => 'রিভিউ দিতে লগইন করুন',
    ];

    public const NUM_DEFAULTS = [
        'otp_expiry_minutes'          => '5',
        'otp_resend_cooldown_seconds' => '60',
        'otp_max_attempts'            => '5',
    ];

    protected static function bool(string $key): bool
    {
        $default = self::BOOL_DEFAULTS[$key] ?? '0';
        return filter_var(WebsiteSetting::get($key, $default), FILTER_VALIDATE_BOOLEAN);
    }

    protected static function int(string $key): int
    {
        $default = self::NUM_DEFAULTS[$key] ?? '0';
        return (int) WebsiteSetting::get($key, $default);
    }

    public static function customerPasswordLogin(): bool { return self::bool('customer_password_login'); }
    public static function customerOtpLogin(): bool      { return self::bool('customer_otp_login'); }
    public static function vendorPasswordLogin(): bool   { return self::bool('vendor_password_login'); }
    public static function vendorOtpLogin(): bool        { return self::bool('vendor_otp_login'); }

    public static function smsEnabled(): bool      { return self::bool('otp_sms_enabled'); }
    public static function whatsappEnabled(): bool { return self::bool('otp_whatsapp_enabled'); }
    public static function emailEnabled(): bool    { return self::bool('otp_email_enabled'); }

    /** Channels offered to a user in the OTP UI, in display order. */
    public static function enabledChannels(): array
    {
        $channels = [];
        if (self::smsEnabled())      { $channels[] = 'sms'; }
        if (self::whatsappEnabled()) { $channels[] = 'whatsapp'; }
        if (self::emailEnabled())    { $channels[] = 'email'; }
        return $channels;
    }

    public static function showEmailFieldOnRegister(): bool { return self::bool('show_email_field_register'); }
    public static function phoneVerificationRequired(): bool { return self::bool('phone_verification_required'); }
    public static function vendorApprovalRequired(): bool   { return self::bool('vendor_approval_required'); }
    public static function vendorAutoApprove(): bool        { return self::bool('vendor_auto_approve'); }

    public static function guestCheckout(): bool { return self::bool('guest_checkout_enabled'); }
    public static function guestEnquiry(): bool  { return self::bool('guest_enquiry_enabled'); }
    public static function guestReview(): bool   { return self::bool('guest_review_enabled'); }

    public static function guestAllowed(string $feature): bool
    {
        return self::bool('guest_'.$feature.'_enabled');
    }

    /** True when the current visitor is a guest AND the feature needs login. Any logged-in user passes. */
    public static function guestBlocked(string $feature): bool
    {
        return ! \Illuminate\Support\Facades\Auth::check() && ! self::guestAllowed($feature);
    }

    /** Customers can actually sign in: login page on and at least one method on. */
    public static function customerLoginPossible(): bool
    {
        return WebsiteSetting::get('customer_login_enabled', '1') === '1'
            && (self::customerPasswordLogin() || self::customerOtpLogin());
    }

    public static function customerRegistrationEnabled(): bool
    {
        return WebsiteSetting::get('customer_registration_enabled', '1') === '1';
    }

    /** Login / register URLs that bring the visitor back to a safe local path. */
    public static function loginUrl(string $redirect): string
    {
        return route('customer.login').'?redirect='.urlencode(self::safePath($redirect));
    }

    public static function registerUrl(string $redirect): string
    {
        return route('customer.register').'?redirect='.urlencode(self::safePath($redirect));
    }

    private static function safePath(string $path): string
    {
        return (str_starts_with($path, '/') && ! str_starts_with($path, '//')) ? $path : '/';
    }

    public static function otpExpiryMinutes(): int       { return max(1, self::int('otp_expiry_minutes')); }
    public static function otpResendCooldownSeconds(): int { return max(0, self::int('otp_resend_cooldown_seconds')); }
    public static function otpMaxAttempts(): int           { return max(1, self::int('otp_max_attempts')); }
}
