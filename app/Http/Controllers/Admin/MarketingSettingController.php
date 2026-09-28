<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetting;
use App\Models\VendorMarketingSetting;
use App\Support\MetaPixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/** Admin → Marketing / Tracking: platform Meta Pixel + vendor pixel oversight. */
class MarketingSettingController extends Controller
{
    public function index()
    {
        $settings = MarketingSetting::current();
        $vendorPixels = VendorMarketingSetting::with('vendor:id,shop_name,status,is_active')
            ->whereNotNull('pixel_id')->orderByDesc('pixel_enabled')->orderBy('vendor_id')->get();

        return view('admin.marketing-settings', compact('settings', 'vendorPixels'));
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
        ], [
            'test_event_code.regex'         => 'Test event code-এ শুধু ইংরেজি অক্ষর ও সংখ্যা দিন।',
            'platform_pixel_scope.required' => 'প্ল্যাটফর্ম পিক্সেলের পরিধি বেছে নিন।',
            'platform_pixel_scope.in'       => 'প্ল্যাটফর্ম পিক্সেলের পরিধি সঠিক নয়।',
        ]);

        $ids = self::splitIds($data['pixel_ids'] ?? '');
        if ($request->boolean('pixel_enabled') && ! $ids) {
            return back()->withInput()->withErrors(['pixel_ids' => 'পিক্সেল চালু করতে অন্তত একটি Pixel ID দিন।']);
        }

        MarketingSetting::current()->update([
            'pixel_enabled'         => $request->boolean('pixel_enabled'),
            'pixel_ids'             => $ids,
            'test_event_code'       => $data['test_event_code'] ?? null,
            'platform_pixel_scope'  => $data['platform_pixel_scope'],
            'track_admin_users'     => $request->boolean('track_admin_users'),
            'vendor_pixels_enabled' => $request->boolean('vendor_pixels_enabled'),
            'vendor_capi_allowed'   => $request->boolean('vendor_capi_allowed'),
        ]);

        Log::info('Marketing settings updated', ['user_id' => $request->user()?->id]);

        return redirect()->route('admin.marketing-settings.index')->with('success', 'মার্কেটিং / ট্র্যাকিং সেটিং সংরক্ষণ হয়েছে।');
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

    /** @return string[] */
    private static function splitIds(?string $value): array
    {
        return array_values(array_unique(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $value)))));
    }
}
