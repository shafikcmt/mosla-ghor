<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceSetting;
use Illuminate\Http\Request;

class GeneralSettingController extends Controller
{
    public function index()
    {
        $settings = PriceSetting::current();

        return view('admin.general-settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'minimum_order_amount'   => 'required|numeric|min:0',
            'default_packaging_cost' => 'required|numeric|min:0',
        ]);

        PriceSetting::current()->update([
            'minimum_order_amount'   => $request->minimum_order_amount,
            'default_packaging_cost' => $request->default_packaging_cost,
        ]);

        return redirect()->route('admin.general-settings.index')
            ->with('success', 'সেটিং আপডেট হয়েছে।');
    }
}
