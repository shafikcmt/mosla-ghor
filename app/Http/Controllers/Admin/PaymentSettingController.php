<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use Illuminate\Http\Request;

class PaymentSettingController extends Controller
{
    public function index()
    {
        $settings = PaymentSetting::current();

        return view('admin.payment-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'bkash_number'        => 'nullable|string|max:20',
            'rocket_number'       => 'nullable|string|max:20',
            'nagad_number'        => 'nullable|string|max:20',
            'payment_instruction' => 'nullable|string|max:1000',
        ]);

        PaymentSetting::current()->update([
            'bkash_number'             => $request->bkash_number ?: null,
            'rocket_number'            => $request->rocket_number ?: null,
            'nagad_number'             => $request->nagad_number ?: null,
            'payment_instruction'      => $request->payment_instruction ?: null,
            'cash_on_delivery_enabled' => $request->boolean('cash_on_delivery_enabled'),
            'bkash_enabled'            => $request->boolean('bkash_enabled'),
            'rocket_enabled'           => $request->boolean('rocket_enabled'),
            'nagad_enabled'            => $request->boolean('nagad_enabled'),
        ]);

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'পেমেন্ট সেটিং আপডেট হয়েছে।');
    }
}
