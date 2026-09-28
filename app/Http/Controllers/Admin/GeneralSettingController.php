<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PriceSetting;
use App\Models\WebsiteSetting;
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

    /** Image compression settings (see App\Support\ImageOptimizer). */
    public function updateImages(Request $request)
    {
        $data = $request->validateWithBag('images', [
            'image_optimize_enabled' => 'nullable|boolean',
            'image_max_product_px'   => 'required|integer|min:600|max:4000',
            'image_quality'          => 'required|integer|min:40|max:95',
        ], [
            'image_max_product_px.required' => 'পণ্যের ছবির সর্বোচ্চ মাপ দিন।',
            'image_max_product_px.integer'  => 'মাপ একটি পূর্ণ সংখ্যা হতে হবে।',
            'image_max_product_px.min'      => 'মাপ কমপক্ষে ৬০০ px।',
            'image_max_product_px.max'      => 'মাপ সর্বোচ্চ ৪০০০ px।',
            'image_quality.required'        => 'কোয়ালিটি দিন।',
            'image_quality.integer'         => 'কোয়ালিটি একটি পূর্ণ সংখ্যা হতে হবে।',
            'image_quality.min'             => 'কোয়ালিটি কমপক্ষে ৪০।',
            'image_quality.max'             => 'কোয়ালিটি সর্বোচ্চ ৯৫।',
        ]);

        foreach ([
            'image_optimize_enabled' => $request->boolean('image_optimize_enabled') ? '1' : '0',
            'image_max_product_px'   => (string) $data['image_max_product_px'],
            'image_quality'          => (string) $data['image_quality'],
        ] as $key => $value) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }

        return redirect()->to(route('admin.general-settings.index').'#image-settings')
            ->with('success', 'ছবির সেটিং সংরক্ষণ হয়েছে।');
    }
}
