<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VendorPayout;
use Illuminate\Http\Request;

class VendorPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = VendorPayout::with('vendor')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payouts = $query->paginate(20)->withQueryString();

        return view('admin.vendor-payouts.index', compact('payouts'));
    }

    public function approve(VendorPayout $vendorPayout)
    {
        $vendorPayout->update(['status' => 'approved']);

        return back()->with('success', 'পেআউট অনুমোদন করা হয়েছে।');
    }

    public function markPaid(VendorPayout $vendorPayout, Request $request)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $vendorPayout->update([
            'status'     => 'paid',
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'পেআউট পরিশোধ করা হয়েছে।');
    }

    public function reject(VendorPayout $vendorPayout, Request $request)
    {
        $request->validate(['admin_note' => 'nullable|string|max:500']);

        $vendorPayout->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'পেআউট প্রত্যাখ্যান করা হয়েছে।');
    }
}
