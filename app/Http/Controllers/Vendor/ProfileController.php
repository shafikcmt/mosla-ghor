<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    public function index()
    {
        $vendor = $this->vendor();

        return view('vendor.profile.index', compact('vendor'));
    }

    public function update(Request $request)
    {
        $vendor = $this->vendor();

        $request->validate([
            'owner_name'    => 'required|string|max:100',
            'phone'         => 'required|string|max:20|unique:vendors,phone,' . $vendor->id,
            'address'       => 'nullable|string|max:500',
            'business_type' => 'nullable|string|max:100',
            'logo'          => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'banner'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'payment_info'  => 'nullable|string|max:1000',
        ]);

        $updates = [
            'owner_name'    => $request->owner_name,
            'phone'         => $request->phone,
            'address'       => $request->address,
            'business_type' => $request->business_type,
        ];

        if ($request->hasFile('logo')) {
            if ($vendor->logo && str_starts_with($vendor->logo, 'storage/')) {
                Storage::disk('public')->delete(preg_replace('#^storage/#', '', $vendor->logo));
            }
            $updates['logo'] = 'storage/' . $request->file('logo')->store('vendors/logos', 'public');
        }

        if ($request->hasFile('banner')) {
            if ($vendor->banner && str_starts_with($vendor->banner, 'storage/')) {
                Storage::disk('public')->delete(preg_replace('#^storage/#', '', $vendor->banner));
            }
            $updates['banner'] = 'storage/' . $request->file('banner')->store('vendors/banners', 'public');
        }

        if ($request->filled('payment_info')) {
            $updates['payment_info'] = ['details' => $request->payment_info];
        }

        $vendor->update($updates);

        return back()->with('success', 'প্রোফাইল আপডেট হয়েছে।');
    }
}
