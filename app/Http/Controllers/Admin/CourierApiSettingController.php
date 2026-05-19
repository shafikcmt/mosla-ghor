<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use Illuminate\Http\Request;

class CourierApiSettingController extends Controller
{
    public function index()
    {
        $couriers = Courier::orderBy('name')->get();
        return view('admin.courier-api-settings.index', compact('couriers'));
    }

    public function update(Request $request, Courier $courier)
    {
        $data = $request->validate([
            'api_enabled' => 'boolean',
            'api_key'     => 'nullable|string|max:255',
            'api_secret'  => 'nullable|string|max:255',
            'base_url'    => 'nullable|string|max:255',
            'status'      => 'required|in:active,inactive',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $data['api_enabled'] = $request->boolean('api_enabled');

        // Never clear existing key/secret if field submitted blank — keep old value
        if (empty($data['api_key'])) unset($data['api_key']);
        if (empty($data['api_secret'])) unset($data['api_secret']);

        $courier->update($data);

        return redirect()->route('admin.courier-api-settings.index')
            ->with('success', $courier->name . ' API সেটিং আপডেট হয়েছে।');
    }
}
