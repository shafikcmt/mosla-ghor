<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    public function index()
    {
        $vendor  = $this->vendor();
        $payouts = $vendor->payouts()->latest()->paginate(20);

        $totalEarned  = $vendor->vendorOrders()->where('status', 'paid')->sum('payable_amount');
        $totalPaid    = $vendor->payouts()->where('status', 'paid')->sum('amount');
        $pendingPayout = $vendor->payouts()->whereIn('status', ['pending', 'approved'])->sum('amount');
        $available    = max(0, $totalEarned - $totalPaid - $pendingPayout);

        return view('vendor.payouts.index', compact('vendor', 'payouts', 'totalEarned', 'totalPaid', 'pendingPayout', 'available'));
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();

        if (! $vendor->isApproved()) {
            return back()->with('error', 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }

        $request->validate([
            'amount'          => 'required|numeric|min:1',
            'payment_method'  => 'required|string|max:50',
            'payment_details' => 'required|string|max:500',
        ], [
            'amount.required'          => 'পরিমাণ দিন।',
            'payment_method.required'  => 'পেমেন্ট পদ্ধতি দিন।',
            'payment_details.required' => 'পেমেন্টের বিবরণ দিন।',
        ]);

        // Available balance check
        $totalEarned   = $vendor->vendorOrders()->where('status', 'paid')->sum('payable_amount');
        $totalPaid     = $vendor->payouts()->where('status', 'paid')->sum('amount');
        $pendingPayout = $vendor->payouts()->whereIn('status', ['pending', 'approved'])->sum('amount');
        $available     = max(0, $totalEarned - $totalPaid - $pendingPayout);

        if ((float) $request->amount > $available) {
            return back()->with('error', 'উপলব্ধ ব্যালেন্স থেকে বেশি পরিমাণ উত্তোলন করা যাবে না।');
        }

        VendorPayout::create([
            'vendor_id'       => $vendor->id,
            'amount'          => $request->amount,
            'payment_method'  => $request->payment_method,
            'payment_details' => $request->payment_details,
            'status'          => 'pending',
        ]);

        return back()->with('success', 'পেআউট রিকুয়েস্ট জমা হয়েছে। অ্যাডমিন অনুমোদন করবেন।');
    }
}
