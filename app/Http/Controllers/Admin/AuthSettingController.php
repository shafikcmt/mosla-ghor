<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class AuthSettingController extends Controller
{
    private const DEFAULTS = [
        'vendor_registration_enabled'   => '0',
        'vendor_login_enabled'          => '1',
        'show_vendor_links_in_header'   => '0',
        'show_vendor_links_in_footer'   => '1',
        'customer_registration_enabled' => '1',
        'customer_login_enabled'        => '1',
        'show_customer_links_in_header' => '1',
        'show_customer_links_in_footer' => '0',
        'vendor_registration_message'   => 'বর্তমানে মার্চেন্ট রেজিস্ট্রেশন চালু নেই। মার্চেন্ট হতে চাইলে অ্যাডমিনের সাথে যোগাযোগ করুন।',
    ];

    public function index()
    {
        $settings = [];
        foreach (self::DEFAULTS as $key => $default) {
            $settings[$key] = WebsiteSetting::get($key, $default);
        }

        return view('admin.auth-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $boolKeys = [
            'vendor_registration_enabled', 'vendor_login_enabled',
            'show_vendor_links_in_header', 'show_vendor_links_in_footer',
            'customer_registration_enabled', 'customer_login_enabled',
            'show_customer_links_in_header', 'show_customer_links_in_footer',
        ];

        foreach ($boolKeys as $key) {
            WebsiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $request->has($key) ? '1' : '0']
            );
        }

        WebsiteSetting::updateOrCreate(
            ['key' => 'vendor_registration_message'],
            ['value' => $request->input('vendor_registration_message', self::DEFAULTS['vendor_registration_message'])]
        );

        return redirect()->route('admin.auth-settings.index')
            ->with('success', 'লগইন ও রেজিস্ট্রেশন সেটিং আপডেট হয়েছে।');
    }
}
