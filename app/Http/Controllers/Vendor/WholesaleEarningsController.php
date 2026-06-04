<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\WholesaleCommissionLedger;
use App\Models\VendorPayout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleEarningsController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor ?? abort(403);
    }

    public function index()
    {
        $vendor  = $this->vendor();
        $summary = WholesaleCommissionLedger::summaryForVendor($vendor->id);

        $ledger = WholesaleCommissionLedger::where('vendor_id', $vendor->id)
            ->with(['enquiry.product', 'order'])
            ->latest()
            ->paginate(20);

        $pendingPayout = VendorPayout::where('vendor_id', $vendor->id)
            ->where('status', 'pending')
            ->sum('amount');

        return view('vendor.earnings.index', compact('vendor', 'summary', 'ledger', 'pendingPayout'));
    }
}
