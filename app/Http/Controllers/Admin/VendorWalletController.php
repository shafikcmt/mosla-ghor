<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\VendorPayout;
use App\Models\WholesaleCommissionLedger;
use App\Models\VendorOrder;
use Illuminate\Http\Request;

class VendorWalletController extends Controller
{
    public function index()
    {
        $vendors = Vendor::where('status', 'approved')
            ->withSum('commissionLedger as total_commission_deducted', 'commission_amount')
            ->withSum('commissionLedger as total_earning', 'vendor_earning')
            ->withSum('payouts as total_paid', 'amount')
            ->get()
            ->map(function ($vendor) {
                $vendor->pending_payout = max(0, ($vendor->total_earning ?? 0) - ($vendor->total_paid ?? 0));
                return $vendor;
            });

        return view('admin.vendor-wallet.index', compact('vendors'));
    }

    public function show(Vendor $vendor)
    {
        $summary = WholesaleCommissionLedger::summaryForVendor($vendor->id);

        // Also include retail commission from vendor_orders
        $retailSummary = VendorOrder::where('vendor_id', $vendor->id)->get();
        $summary['retail_sales']      = $retailSummary->sum('subtotal');
        $summary['retail_commission'] = $retailSummary->sum('commission_amount');
        $summary['retail_earning']    = $retailSummary->sum('payable_amount');

        $payouts = VendorPayout::where('vendor_id', $vendor->id)->latest()->get();
        $paid    = $payouts->where('status', 'paid')->sum('amount');

        $summary['paid_amount']    = $paid;
        $summary['pending_payout'] = max(0, ($summary['total_earning'] + $summary['retail_earning']) - $paid);

        $ledger = WholesaleCommissionLedger::where('vendor_id', $vendor->id)
            ->with(['enquiry.product', 'order'])
            ->latest()
            ->paginate(20);

        return view('admin.vendor-wallet.show', compact('vendor', 'summary', 'ledger', 'payouts'));
    }
}
