<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetting;
use App\Models\VendorMarketingSetting;
use App\Support\MetaCapi;
use App\Support\MetaPixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Admin → Marketing / Tracking: platform Meta Pixel + Conversions API + vendor pixel oversight. */
class MarketingSettingController extends Controller
{
    public function index()
    {
        $settings = MarketingSetting::current();
        $vendorPixels = VendorMarketingSetting::with('vendor:id,shop_name,status,is_active')
            ->whereNotNull('pixel_id')->orderByDesc('pixel_enabled')->orderBy('vendor_id')->get();

        $visitCount = \Illuminate\Support\Facades\Schema::hasTable('marketing_visits') ? \App\Models\MarketingVisit::count() : 0;

        return view('admin.marketing-settings', compact('settings', 'vendorPixels', 'visitCount'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'pixel_enabled'         => 'nullable|boolean',
            'pixel_ids'             => ['nullable', 'string', 'max:200', function ($attribute, $value, $fail) {
                $ids = self::splitIds($value);
                if (count($ids) > 5) {
                    $fail('সর্বোচ্চ ৫টি Pixel ID দেওয়া যাবে।');
                }
                foreach ($ids as $id) {
                    if (! MetaPixel::validId($id)) {
                        $fail('Pixel ID শুধু ১০–২০ অঙ্কের সংখ্যা হতে পারে। কোড বা স্ক্রিপ্ট দেবেন না।');
                        return;
                    }
                }
            }],
            'test_event_code'       => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
            'platform_pixel_scope'  => 'required|in:all,own',
            'track_admin_users'     => 'nullable|boolean',
            'vendor_pixels_enabled' => 'nullable|boolean',
            'vendor_capi_allowed'   => 'nullable|boolean',
            'capi_enabled'          => 'nullable|boolean',
            'capi_access_token'     => ['nullable', 'string', 'max:1000', 'regex:/^[A-Za-z0-9_\-]+$/'],
            'capi_token_clear'      => 'nullable|boolean',
            'visit_logging_enabled' => 'nullable|boolean',
        ], [
            'capi_access_token.regex'       => 'Access token-এ শুধু অক্ষর/সংখ্যা থাকে — স্পেস বা অন্য কিছু পেস্ট হয়েছে কিনা দেখুন।',
            'test_event_code.regex'         => 'Test event code-এ শুধু ইংরেজি অক্ষর ও সংখ্যা দিন।',
            'platform_pixel_scope.required' => 'প্ল্যাটফর্ম পিক্সেলের পরিধি বেছে নিন।',
            'platform_pixel_scope.in'       => 'প্ল্যাটফর্ম পিক্সেলের পরিধি সঠিক নয়।',
        ]);

        $ids = self::splitIds($data['pixel_ids'] ?? '');
        if ($request->boolean('pixel_enabled') && ! $ids) {
            return back()->withInput($request->except('capi_access_token'))->withErrors(['pixel_ids' => 'পিক্সেল চালু করতে অন্তত একটি Pixel ID দিন।']);
        }

        $settings = MarketingSetting::current();
        $newToken = $data['capi_access_token'] ?? null;
        $clearToken = $request->boolean('capi_token_clear') && ! $newToken;
        $hasToken = $newToken || (! $clearToken && MetaCapi::token($settings));
        if ($request->boolean('capi_enabled') && ! $hasToken) {
            return back()->withInput($request->except('capi_access_token'))->withErrors(['capi_access_token' => 'Conversions API চালু করতে Access token দিন।']);
        }
        if ($request->boolean('capi_enabled') && ! ($request->boolean('pixel_enabled') && $ids)) {
            return back()->withInput($request->except('capi_access_token'))->withErrors(['capi_enabled' => 'Conversions API চালাতে পিক্সেল চালু ও Pixel ID দেওয়া থাকতে হবে।']);
        }

        $update = [
            'pixel_enabled'         => $request->boolean('pixel_enabled'),
            'pixel_ids'             => $ids,
            'test_event_code'       => $data['test_event_code'] ?? null,
            // A Test Events code only works for 2 hours after it is saved, so it can't be left on by accident.
            'test_event_code_expires_at' => ! empty($data['test_event_code']) ? self::testCodeExpiry($settings, $data['test_event_code']) : null,
            'platform_pixel_scope'  => $data['platform_pixel_scope'],
            'track_admin_users'     => $request->boolean('track_admin_users'),
            'vendor_pixels_enabled' => $request->boolean('vendor_pixels_enabled'),
            'vendor_capi_allowed'   => $request->boolean('vendor_capi_allowed'),
            'capi_enabled'          => $request->boolean('capi_enabled'),
        ];
        if ($newToken) {
            $update['capi_access_token'] = $newToken;
        } elseif ($clearToken) {
            $update['capi_access_token'] = null;
        }
        $settings->update($update);
        \App\Models\WebsiteSetting::updateOrCreate(
            ['key' => \App\Support\VisitLogger::SETTING],
            ['value' => $request->boolean('visit_logging_enabled') ? '1' : '0']
        );

        Log::info('Marketing settings updated', ['user_id' => $request->user()?->id]);

        return redirect()->route('admin.marketing-settings.index')->with('success', 'মার্কেটিং / ট্র্যাকিং সেটিং সংরক্ষণ হয়েছে।');
    }

    /** Delete old rows from marketing_visits ONLY (never orders, enquiries or any other table). */
    public function pruneVisits(Request $request)
    {
        $data = $request->validate(['days' => 'required|integer|min:7|max:3650'], [
            'days.min' => 'কমপক্ষে ৭ দিনের পুরোনো রেকর্ড মোছা যাবে।',
        ]);
        $removed = \App\Models\MarketingVisit::pruneOlderThan((int) $data['days']);
        Log::info('Marketing visits pruned', ['user_id' => $request->user()?->id, 'days' => (int) $data['days'], 'removed' => $removed]);

        return redirect()->route('admin.marketing-settings.index')
            ->with('success', "{$removed}টি পুরোনো ভিজিট রেকর্ড মুছে ফেলা হয়েছে।");
    }

    /**
     * Send one test PageView through the Conversions API. Requires a test event code,
     * so it only ever shows up under Events Manager → Test Events.
     */
    public function testCapi(Request $request)
    {
        $settings = MarketingSetting::current();
        if (! MetaCapi::activeTestCode($settings)) {
            return back()->with('error', 'আগে (বা নতুন করে) Test event code দিয়ে সংরক্ষণ করুন — কোডটি সংরক্ষণের ২ ঘণ্টা পর্যন্ত কাজ করে (Events Manager → Test Events থেকে পাবেন)।');
        }
        if (! MetaCapi::token($settings) || ! MetaPixel::platformIds()) {
            return back()->with('error', 'পিক্সেল চালু, Pixel ID ও Access token দিয়ে আগে সংরক্ষণ করুন।');
        }

        [$ok, $message] = MetaCapi::send([MetaCapi::event('PageView', 'capi-test-'.now()->timestamp, [],
            MetaCapi::userData([]), url('/'))]);

        return back()->with($ok ? 'success' : 'error', $ok
            ? "টেস্ট ইভেন্ট পাঠানো হয়েছে — {$message} Events Manager → Test Events-এ দেখুন।"
            : "টেস্ট ব্যর্থ: {$message}");
    }

    /** Admin override: switch a vendor's pixel off (or back on). Vendors cannot change this. */
    public function toggleVendor(Request $request, VendorMarketingSetting $vendorSetting)
    {
        $vendorSetting->forceFill(['admin_blocked' => ! $vendorSetting->admin_blocked])->save();

        Log::info('Vendor pixel '.($vendorSetting->admin_blocked ? 'blocked' : 'unblocked').' by admin', [
            'user_id' => $request->user()?->id, 'vendor_id' => $vendorSetting->vendor_id,
        ]);

        return back()->with('success', $vendorSetting->admin_blocked
            ? 'ভেন্ডরের পিক্সেল বন্ধ করা হয়েছে।'
            : 'ভেন্ডরের পিক্সেল আবার চালু করা হয়েছে।');
    }

    /** Same code re-saved keeps its running window; a new/changed code starts a fresh 2-hour window. */
    private static function testCodeExpiry(MarketingSetting $settings, string $code)
    {
        if ($settings->test_event_code === $code && $settings->test_event_code_expires_at?->isFuture()) {
            return $settings->test_event_code_expires_at;
        }
        return now()->addHours(2);
    }

    /** @return string[] */
    private static function splitIds(?string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $value)))));
    }
}
