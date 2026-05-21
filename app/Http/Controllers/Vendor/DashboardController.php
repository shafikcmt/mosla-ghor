<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorOrder;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $vendor = Auth::user()->vendor;

        if (! $vendor) {
            Auth::logout();
            return redirect()->route('vendor.login')->with('error', 'ভেন্ডর প্রোফাইল পাওয়া যায়নি।');
        }

        $stats = [];

        if ($vendor->isApproved()) {
            $stats['total_products']  = $vendor->products()->count();
            $stats['active_products'] = $vendor->products()->where('is_active', true)->count();
            $stats['total_combos']    = $vendor->combos()->count();
            $stats['total_orders']    = $vendor->vendorOrders()->count();
            $stats['pending_orders']  = $vendor->vendorOrders()->where('status', 'pending')->count();
            $stats['total_earned']    = $vendor->vendorOrders()->where('status', 'paid')->sum('payable_amount');
            $stats['pending_payout']  = $vendor->payouts()->whereIn('status', ['pending', 'approved'])->sum('amount');

            $recentOrders = $vendor->vendorOrders()
                ->with('order')
                ->latest()
                ->limit(5)
                ->get();
        } else {
            $recentOrders = collect();
        }

        return view('vendor.dashboard', compact('vendor', 'stats', 'recentOrders'));
    }
}
