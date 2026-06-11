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
            'bkash_number'             => 'nullable|string|max:20',
            'rocket_number'            => 'nullable|string|max:20',
            'nagad_number'             => 'nullable|string|max:20',
            'payment_instruction'      => 'nullable|string|max:1000',
            'instant_discount_type'    => 'required|in:fixed,percentage',
            'instant_discount_value'   => 'required|numeric|min:0',
            'instant_min_order_amount' => 'nullable|numeric|min:0',
            'cod_delivery_days'        => 'nullable|string|max:30',
            'instant_delivery_days'    => 'nullable|string|max:30',
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
            'instant_payment_enabled'  => $request->boolean('instant_payment_enabled'),
            'instant_discount_type'    => $request->instant_discount_type,
            'instant_discount_value'   => $request->instant_discount_value,
            'instant_min_order_amount' => $request->instant_min_order_amount !== null && $request->instant_min_order_amount !== ''
                                            ? $request->instant_min_order_amount : null,
            'cod_delivery_days'        => $request->cod_delivery_days ?: '৫–৭',
            'instant_delivery_days'    => $request->instant_delivery_days ?: '২–৩',
        ]);

        return redirect()->route('admin.payment-settings.index')
            ->with('success', 'পেমেন্ট সেটিং আপডেট হয়েছে।');
    }
}
