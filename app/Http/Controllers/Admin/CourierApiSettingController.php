<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CourierDiagnosticsInterface;
use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierSetting;
use App\Services\CourierDriverFactory;
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
            'api_enabled'     => 'nullable|boolean',
            'base_url'        => 'nullable|string|max:255',
            'base_url_select' => 'nullable|string|max:255',
            'base_url_custom' => 'nullable|string|max:255',
            'status'          => 'required|in:active,inactive',
            'notes'           => 'nullable|string|max:1000',
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

        // Resolve base URL from the dropdown (preset) or the custom field.
        if ($request->filled('base_url_select')) {
            $sel = $request->input('base_url_select');
            $resolved = $sel === 'custom' ? trim((string) $request->input('base_url_custom')) : $sel;
            $data['base_url'] = $resolved !== '' ? $resolved : null;
        }
        unset($data['base_url_select'], $data['base_url_custom']);

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
    public function test(Courier $courier, CourierDriverFactory $drivers)
    {
        if (! $courier->supportsApi()) {
            return redirect()->route('admin.courier-api-settings.index')
                ->with('error', $courier->name . ' এর জন্য API টেস্ট সাপোর্ট নেই (ম্যানুয়াল কুরিয়ার)।');
        }

        $result = $drivers->for($courier)->testConnection($courier);

        // level → flash key: success (green) | warning (yellow) | error (red)
        $flashKey = $result['success'] ? 'success' : ($result['level'] ?? 'error');
        if (! in_array($flashKey, ['success', 'warning', 'error'], true)) {
            $flashKey = 'error';
        }

        return redirect()->route('admin.courier-api-settings.index')
            ->with($flashKey, $courier->name . ' টেস্ট: ' . $result['message']);
    }

    /**
     * Run a single diagnostic (dns | ssl | balance | full) for an API courier.
     */
    public function diagnose(Request $request, Courier $courier, CourierDriverFactory $drivers)
    {
        $driver = $drivers->for($courier);

        if (! $courier->supportsApi() || ! $driver instanceof CourierDiagnosticsInterface) {
            return redirect()->route('admin.courier-api-settings.index')
                ->with('error', $courier->name . ' এর জন্য API ডায়াগনস্টিক সাপোর্ট নেই (ম্যানুয়াল কুরিয়ার)।');
        }

        $type = $request->input('type', 'full');
        if (! in_array($type, ['dns', 'ssl', 'balance', 'full'], true)) {
            $type = 'full';
        }

        $result = match ($type) {
            'dns'     => $driver->testDns($courier),
            'ssl'     => $driver->testSsl($courier),
            'balance' => $driver->testConnection($courier),
            default   => $driver->fullTest($courier),
        };

        $labels  = ['dns' => 'DNS', 'ssl' => 'SSL', 'balance' => 'Balance', 'full' => 'Full'];
        $flashKey = $result['success'] ? 'success' : ($result['level'] ?? 'error');
        if (! in_array($flashKey, ['success', 'warning', 'error'], true)) {
            $flashKey = 'error';
        }

        return redirect()->route('admin.courier-api-settings.index')
            ->with($flashKey, $courier->name . ' — ' . ($labels[$type] ?? 'Test') . ' টেস্ট: ' . $result['message']);
    }

    /**
     * Save vendor courier-permission settings.
     */
    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'vendor_courier_mode' => 'required|in:admin_only,vendor_can_request,vendor_can_parcel',
        ], [
            'vendor_courier_mode.required' => 'কুরিয়ার মোড নির্বাচন করুন।',
            'vendor_courier_mode.in'       => 'কুরিয়ার মোড সঠিক নয়।',
        ]);

        $mode = $data['vendor_courier_mode'];

        $settings = CourierSetting::current();
        $settings->update([
            'vendor_courier_mode'             => $mode,
            // Keep the legacy column consistent so nothing reading it breaks.
            'courier_selection_mode'          => CourierSetting::MODE_TO_LEGACY[$mode] ?? 'admin_only',
            'vendor_can_select_courier'       => $request->boolean('vendor_can_select_courier'),
            'vendor_can_update_tracking'      => $request->boolean('vendor_can_update_tracking'),
            'vendor_can_mark_handover'        => $request->boolean('vendor_can_mark_handover'),
            'vendor_can_setup_pickup_address' => $request->boolean('vendor_can_setup_pickup_address'),
            'vendor_can_create_parcel'        => $request->boolean('vendor_can_create_parcel'),
        ]);

        return redirect()->route('admin.courier-api-settings.index')
            ->with('success', 'ভেন্ডর কুরিয়ার পারমিশন সেটিং সংরক্ষণ হয়েছে।');
    }
}
