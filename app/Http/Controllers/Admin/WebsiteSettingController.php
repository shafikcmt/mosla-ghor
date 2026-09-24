<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class WebsiteSettingController extends Controller
{
    private const FIELDS = [
        'site_name', 'hero_badge_text', 'hero_title', 'hero_subtitle',
        'primary_cta_text', 'secondary_cta_text', 'hero_image_url',
        'whatsapp_number', 'messenger_url', 'facebook_page_url', 'footer_text',
        'vendor_registration_enabled', 'vendor_login_enabled',
        'show_vendor_links_in_header', 'show_vendor_links_in_footer',
        // Header announcement
        'announcement_enabled', 'announcement_text_1',
        'announcement_link_url', 'announcement_link_label',
    ];

    public function index()
    {
        $settings = WebsiteSetting::allKeyed();

        return view('admin.website-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'site_name'        => 'required|string|max:100',
            'hero_title'       => 'required|string|max:200',
            'hero_subtitle'    => 'nullable|string|max:500',
            'hero_badge_text'  => 'nullable|string|max:100',
            'primary_cta_text' => 'nullable|string|max:60',
            'secondary_cta_text' => 'nullable|string|max:60',
            'hero_image_url'   => 'nullable|string|max:500',
            'whatsapp_number'  => 'nullable|string|max:20',
            'messenger_url'    => 'nullable|string|max:300',
            'facebook_page_url' => 'nullable|string|max:300',
            'footer_text'      => 'nullable|string|max:200',
            // Header announcement.
            // If the bar is enabled, the primary text is required.
            'announcement_text_1'   => 'nullable|string|max:255|required_if:announcement_enabled,1',
            'announcement_enabled' => 'nullable|boolean',
            'announcement_link_url' => ['nullable', 'string', 'max:300', function ($attribute, $value, $fail) {
                if (! \App\Support\AnnouncementUrl::isValid($value)) {
                    $fail('Use an internal path beginning with / or a valid http/https URL.');
                }
            }],
            'announcement_link_label' => 'nullable|string|max:60',
        ], [
            'announcement_text_1.required_if' => 'অ্যানাউন্সমেন্ট বার চালু থাকলে টেক্সট দেওয়া আবশ্যক।',
        ]);

        $boolFields = [
            'vendor_registration_enabled', 'vendor_login_enabled',
            'show_vendor_links_in_header', 'show_vendor_links_in_footer',
            'announcement_enabled',
        ];

        foreach (self::FIELDS as $key) {
            $value = in_array($key, $boolFields)
                ? ($request->boolean($key) ? '1' : '0')
                : $request->input($key, '');

            WebsiteSetting::updateOrCreate(
                ['key' => $key],
                ['value' => $value]
            );
        }

        return redirect()->route('admin.website-settings.index')
            ->with('success', 'ওয়েবসাইট সেটিং আপডেট হয়েছে।');
    }
}
