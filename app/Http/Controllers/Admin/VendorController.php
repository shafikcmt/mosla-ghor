<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $query = Vendor::with('user')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('shop_name', 'like', "%$s%")
                  ->orWhere('owner_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('email', 'like', "%$s%");
            });
        }

        $vendors = $query->paginate(20)->withQueryString();

        return view('admin.vendors.index', compact('vendors'));
    }

    public function show(Vendor $vendor)
    {
        $vendor->load('user');
        $products    = $vendor->products()->orderByDesc('id')->limit(10)->get();
        $combos      = $vendor->combos()->orderByDesc('id')->limit(10)->get();
        $recentOrders = $vendor->vendorOrders()->with('order')->latest()->limit(10)->get();
        $recentPayouts = $vendor->payouts()->latest()->limit(5)->get();

        return view('admin.vendors.show', compact('vendor', 'products', 'combos', 'recentOrders', 'recentPayouts'));
    }

    public function approve(Vendor $vendor)
    {
        $vendor->update(['status' => 'approved', 'is_active' => true]);

        return back()->with('success', 'ভেন্ডর অনুমোদন করা হয়েছে।');
    }

    public function reject(Vendor $vendor)
    {
        $vendor->update(['status' => 'rejected', 'is_active' => false]);

        return back()->with('success', 'ভেন্ডর প্রত্যাখ্যান করা হয়েছে।');
    }

    public function suspend(Vendor $vendor)
    {
        $vendor->update(['status' => 'suspended', 'is_active' => false]);

        return back()->with('success', 'ভেন্ডর স্থগিত করা হয়েছে।');
    }

    public function reactivate(Vendor $vendor)
    {
        $vendor->update(['status' => 'approved', 'is_active' => true]);

        return back()->with('success', 'ভেন্ডর পুনরায় সক্রিয় করা হয়েছে।');
    }

    public function edit(Vendor $vendor)
    {
        return view('admin.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $request->validate([
            'shop_name'        => 'required|string|max:150',
            'owner_name'       => 'required|string|max:100',
            'commission_type'  => 'nullable|in:percentage,fixed',
            'commission_value' => 'nullable|numeric|min:0',
            'product_auto_approve' => 'boolean',
            'admin_note'       => 'nullable|string|max:1000',
        ]);

        $vendor->update([
            'shop_name'            => $request->shop_name,
            'owner_name'           => $request->owner_name,
            'commission_type'      => $request->commission_type ?: null,
            'commission_value'     => $request->commission_value ?: null,
            'product_auto_approve' => $request->boolean('product_auto_approve'),
            'admin_note'           => $request->admin_note ?: null,
        ]);

        return back()->with('success', 'ভেন্ডর তথ্য আপডেট হয়েছে।');
    }

    public function settings()
    {
        $settings = [
            'vendor_registration_enabled' => WebsiteSetting::get('vendor_registration_enabled', '1'),
            'vendor_auto_approve'          => WebsiteSetting::get('vendor_auto_approve', '0'),
            'vendor_product_auto_approve'  => WebsiteSetting::get('vendor_product_auto_approve', '0'),
            'default_commission_type'      => WebsiteSetting::get('default_commission_type', 'percentage'),
            'default_commission_value'     => WebsiteSetting::get('default_commission_value', '0'),
        ];

        return view('admin.vendors.settings', compact('settings'));
    }

    public function saveSettings(Request $request)
    {
        $request->validate([
            'default_commission_type'  => 'required|in:percentage,fixed',
            'default_commission_value' => 'required|numeric|min:0',
        ]);

        $keys = [
            'vendor_registration_enabled',
            'vendor_auto_approve',
            'vendor_product_auto_approve',
            'default_commission_type',
            'default_commission_value',
        ];

        foreach ($keys as $key) {
            WebsiteSetting::updateOrCreate(['key' => $key], ['value' => $request->input($key, '0')]);
        }

        return back()->with('success', 'মাল্টিভেন্ডর সেটিং আপডেট হয়েছে।');
    }
}
