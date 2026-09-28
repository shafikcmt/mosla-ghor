<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\MarketingSetting;
use App\Models\VendorMarketingSetting;
use App\Support\MetaPixel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Vendor → Profile → Marketing: the vendor's own Meta Pixel (numeric ID only). */
class MarketingController extends Controller
{
    private function setting(): VendorMarketingSetting
    {
        abort_unless(MarketingSetting::current()->vendor_pixels_enabled, 404);
        $vendor = Auth::user()->vendor ?? abort(403);

        return VendorMarketingSetting::firstOrCreate(['vendor_id' => $vendor->id]);
    }

    public function edit()
    {
        return view('vendor.profile.marketing', ['setting' => $this->setting()]);
    }

    public function update(Request $request)
    {
        $setting = $this->setting();

        $data = $request->validate([
            'pixel_enabled'   => 'nullable|boolean',
            'pixel_id'        => ['nullable', 'required_if:pixel_enabled,1', 'string', 'regex:'.MetaPixel::ID_PATTERN],
            'test_event_code' => ['nullable', 'string', 'max:30', 'regex:/^[A-Za-z0-9]+$/'],
        ], [
            'pixel_id.required_if'  => 'পিক্সেল চালু করতে Pixel ID দিন।',
            'pixel_id.regex'        => 'Pixel ID শুধু ১০–২০ অঙ্কের সংখ্যা হতে পারে। কোড বা স্ক্রিপ্ট দেবেন না।',
            'test_event_code.regex' => 'Test event code-এ শুধু ইংরেজি অক্ষর ও সংখ্যা দিন।',
            'test_event_code.max'   => 'Test event code সর্বোচ্চ ৩০ অক্ষর।',
        ]);

        // admin_blocked / last_event_at are not fillable — vendors can never change them.
        $setting->update([
            'pixel_enabled'   => $request->boolean('pixel_enabled'),
            'pixel_id'        => $data['pixel_id'] ?? null,
            'test_event_code' => $data['test_event_code'] ?? null,
        ]);

        return redirect()->route('vendor.profile.marketing')->with('success', 'মার্কেটিং সেটিং সংরক্ষণ হয়েছে।');
    }
}
