<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use Illuminate\Http\Request;

class DeliverySettingController extends Controller
{
    public function index()
    {
        $settings = DeliverySetting::current();

        return view('admin.delivery-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'inside_dhaka_charge'          => 'required|numeric|min:0',
            'outside_dhaka_charge'         => 'required|numeric|min:0',
            'free_delivery_minimum_amount' => 'nullable|numeric|min:0',
            'delivery_note'                => 'nullable|string|max:1000',
        ]);

        DeliverySetting::current()->update([
            'inside_dhaka_charge'          => $request->inside_dhaka_charge,
            'outside_dhaka_charge'         => $request->outside_dhaka_charge,
            'free_delivery_minimum_amount' => $request->free_delivery_minimum_amount ?: null,
            'enable_free_delivery'         => $request->boolean('enable_free_delivery'),
            'delivery_note'                => $request->delivery_note ?: null,
        ]);

        return redirect()->route('admin.delivery-settings.index')
            ->with('success', 'ডেলিভারি সেটিং আপডেট হয়েছে।');
    }
}
