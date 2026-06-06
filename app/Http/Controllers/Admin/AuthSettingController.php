<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Support\AuthSettings;
use Illuminate\Http\Request;

class AuthSettingController extends Controller
{
    /** Page-specific toggles not covered by {@see AuthSettings}. */
    private const PAGE_BOOL_DEFAULTS = [
        'vendor_registration_enabled'   => '0',
        'vendor_login_enabled'          => '1',
        'show_vendor_links_in_header'   => '0',
        'show_vendor_links_in_footer'   => '1',
        'customer_registration_enabled' => '1',
        'customer_login_enabled'        => '1',
        'show_customer_links_in_header' => '1',
        'show_customer_links_in_footer' => '0',
    ];

    private const TEXT_DEFAULTS = [
        'vendor_registration_message'   => 'বর্তমানে মার্চেন্ট রেজিস্ট্রেশন চালু নেই। মার্চেন্ট হতে চাইলে অ্যাডমিনের সাথে যোগাযোগ করুন।',
    ];

    /**
     * AuthSettings bool keys this form actually renders as checkboxes. Only
     * these are persisted on save — keys NOT listed here (e.g. those whose
     * default is '1') would otherwise be silently forced to '0' every save.
     */
    private const FORM_AUTH_BOOL_KEYS = [
        'customer_password_login', 'customer_otp_login',
        'vendor_password_login', 'vendor_otp_login', 'vendor_auto_approve',
        'otp_sms_enabled', 'otp_whatsapp_enabled', 'otp_email_enabled',
        'show_email_field_register',
    ];

    public function index()
    {
        $settings = [];
        $defaults = array_merge(
            self::PAGE_BOOL_DEFAULTS,
            AuthSettings::BOOL_DEFAULTS,
            AuthSettings::NUM_DEFAULTS,
            self::TEXT_DEFAULTS,
        );

        foreach ($defaults as $key => $default) {
            $settings[$key] = WebsiteSetting::get($key, $default);
        }

        return view('admin.auth-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $nums = $request->validate([
            'otp_expiry_minutes'          => 'required|integer|min:1|max:60',
            'otp_resend_cooldown_seconds' => 'required|integer|min:0|max:600',
            'otp_max_attempts'            => 'required|integer|min:1|max:10',
        ], [
            'otp_expiry_minutes.required'          => 'OTP মেয়াদ দিন।',
            'otp_expiry_minutes.max'               => 'OTP মেয়াদ সর্বোচ্চ ৬০ মিনিট।',
            'otp_resend_cooldown_seconds.required' => 'পুনরায় পাঠানোর সময় দিন।',
            'otp_max_attempts.required'            => 'সর্বোচ্চ চেষ্টার সংখ্যা দিন।',
        ]);

        $boolKeys = array_merge(
            array_keys(self::PAGE_BOOL_DEFAULTS),
            self::FORM_AUTH_BOOL_KEYS,
        );

        foreach ($boolKeys as $key) {
            WebsiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->has($key) ? '1' : '0']
            );
        }

        foreach (array_keys(AuthSettings::NUM_DEFAULTS) as $key) {
            WebsiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => (string) $nums[$key]]
            );
        }

        WebsiteSetting::updateOrCreate(
            ['key' => 'vendor_registration_message'],
            ['value' => $request->input('vendor_registration_message', self::TEXT_DEFAULTS['vendor_registration_message'])]
        );

        return redirect()->route('admin.auth-settings.index')
            ->with('success', 'লগইন ও রেজিস্ট্রেশন সেটিং আপডেট হয়েছে।');
    }
}
