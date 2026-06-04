<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierSetting;
use App\Services\SteadfastService;
use Illuminate\Http\Request;

class CourierApiSettingController extends Controller
{
    public function index()
    {
        $couriers = Courier::orderBy('name')->get();
        $settings = CourierSetting::current();

        return view('admin.courier-api-settings.index', compact('couriers', 'settings'));
    }

    public function update(Request $request, Courier $courier)
    {
        // Credentials are only touched when the admin explicitly opts in. This blocks
        // browser autofill (e.g. the admin login email) from silently overwriting keys.
        $replace = $request->boolean('replace_api_credentials');

        $rules = [
            'api_enabled' => 'nullable|boolean',
            'base_url'    => 'nullable|string|max:255',
            'status'      => 'required|in:active,inactive',
            'notes'       => 'nullable|string|max:1000',
        ];

        if ($replace) {
            $rules['api_key']    = ['nullable', 'string', 'max:255', $this->notLoginEmailRule()];
            $rules['api_secret'] = ['nullable', 'string', 'max:255', $this->notLoginEmailRule()];
        }

        $data = $request->validate($rules, [
            'status.required' => 'স্ট্যাটাস নির্বাচন করুন।',
            'status.in'       => 'স্ট্যাটাস সঠিক নয়।',
            'base_url.max'    => 'Base URL ২৫৫ অক্ষরের বেশি হতে পারবে না।',
        ]);

        $data['api_enabled'] = $request->boolean('api_enabled');

        // Ignore credential fields unless the admin opted in; blank means "leave unchanged".
        if (! $replace || blank($request->input('api_key')))    unset($data['api_key']);
        if (! $replace || blank($request->input('api_secret'))) unset($data['api_secret']);

        // Warn (but still save) if API is enabled without credentials.
        $courier->fill($data);

        if ($courier->api_enabled && $courier->supportsApi()
            && (empty($courier->api_key) || empty($courier->api_secret))) {
            $courier->save();

            return redirect()->route('admin.courier-api-settings.index')
                ->with('error', $courier->name . ' সংরক্ষিত হয়েছে, তবে API চালু আছে কিন্তু API Key/Secret কনফিগার করা নেই। অনুগ্রহ করে credential দিন।');
        }

        $courier->save();

        return redirect()->route('admin.courier-api-settings.index')
            ->with('success', $courier->name . ' API সেটিং সফলভাবে সংরক্ষণ হয়েছে।');
    }

    /**
     * Test connectivity / credentials for an API courier.
     */
    public function test(Courier $courier, SteadfastService $steadfast)
    {
        if (! $courier->supportsApi()) {
            return redirect()->route('admin.courier-api-settings.index')
                ->with('error', $courier->name . ' এর জন্য API টেস্ট সাপোর্ট নেই (ম্যানুয়াল কুরিয়ার)।');
        }

        $result = $steadfast->testConnection($courier);

        // level → flash key: success (green) | warning (yellow) | error (red)
        $flashKey = $result['success'] ? 'success' : ($result['level'] ?? 'error');
        if (! in_array($flashKey, ['success', 'warning', 'error'], true)) {
            $flashKey = 'error';
        }

        return redirect()->route('admin.courier-api-settings.index')
            ->with($flashKey, $courier->name . ' টেস্ট: ' . $result['message']);
    }

    /**
     * Save vendor courier-permission settings.
     */
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'courier_selection_mode' => 'required|in:admin_only,vendor_suggest,vendor_select',
        ], [
            'courier_selection_mode.required' => 'কুরিয়ার সিলেকশন মোড নির্বাচন করুন।',
            'courier_selection_mode.in'       => 'কুরিয়ার সিলেকশন মোড সঠিক নয়।',
        ]);

        $settings = CourierSetting::current();
        $settings->update([
            'courier_selection_mode'     => $data['courier_selection_mode'],
            'vendor_can_select_courier'  => $request->boolean('vendor_can_select_courier'),
            'vendor_can_update_tracking' => $request->boolean('vendor_can_update_tracking'),
            'vendor_can_mark_handover'   => $request->boolean('vendor_can_mark_handover'),
        ]);

        return redirect()->route('admin.courier-api-settings.index')
            ->with('success', 'ভেন্ডর কুরিয়ার পারমিশন সেটিং সংরক্ষণ হয়েছে।');
    }
}
