<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\CourierDiagnosticsInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCourierApiSettingsRequest;
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

    public function update(UpdateCourierApiSettingsRequest $request, Courier $courier)
    {
        $data = $request->safe()->only(['api_key', 'api_secret', 'base_url']);
        if ($request->filled('base_url_select')) {
            $data['base_url'] = $request->input('base_url_select');
        }
        foreach (['api_key', 'api_secret'] as $field) {
            if (! $request->boolean('replace_api_credentials') || ! $request->filled($field)) {
                unset($data[$field]);
            }
        }
        $data['api_enabled'] = $request->boolean('api_enabled');
        $courier->fill($data);
        if ($courier->isDirty(['api_key', 'api_secret', 'base_url'])) {
            $courier->courier_api_last_checked_at = null;
            $courier->courier_api_last_status = null;
            $courier->courier_api_last_error = null;
            $courier->courier_api_last_message = null;
        }
        $courier->save();

        return redirect()->route('admin.courier-api-settings.index', ['courier' => $courier->id, 'tab' => 'api'])
            ->with('success', $courier->name . ' API settings saved.');
    }

    /**
     * Test connectivity / credentials for an API courier.
     */
    public function test(Courier $courier, CourierDriverFactory $drivers)
    {
        if (! $courier->supportsApi()) {
            return redirect()->route('admin.courier-api-settings.index', ['courier' => $courier->id, 'tab' => 'api'])
                ->with('error', $courier->name . ' এর জন্য API টেস্ট সাপোর্ট নেই (ম্যানুয়াল কুরিয়ার)।');
        }

        $result = $drivers->for($courier)->testConnection($courier);

        // level → flash key: success (green) | warning (yellow) | error (red)
        $flashKey = $result['success'] ? 'success' : ($result['level'] ?? 'error');
        if (! in_array($flashKey, ['success', 'warning', 'error'], true)) {
            $flashKey = 'error';
        }

        return redirect()->route('admin.courier-api-settings.index', ['courier' => $courier->id, 'tab' => 'api'])
            ->with($flashKey, $courier->name . ' টেস্ট: ' . $result['message']);
    }

    /**
     * Run a single diagnostic (dns | ssl | balance | full) for an API courier.
     */
    public function diagnose(Request $request, Courier $courier, CourierDriverFactory $drivers)
    {
        $driver = $drivers->for($courier);

        if (! $courier->supportsApi() || ! $driver instanceof CourierDiagnosticsInterface) {
            return redirect()->route('admin.courier-api-settings.index', ['courier' => $courier->id, 'tab' => 'api'])
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

        return redirect()->route('admin.courier-api-settings.index', ['courier' => $courier->id, 'tab' => 'api'])
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
