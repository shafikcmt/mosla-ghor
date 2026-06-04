<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesaleCommissionSetting;
use App\Models\WholesaleCommissionLedger;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleCommissionController extends Controller
{
    public function index()
    {
        $settings = WholesaleCommissionSetting::with([])->orderBy('scope')->get();
        $vendors  = Vendor::where('status', 'approved')->orderBy('shop_name')->get();

        return view('admin.commission-settings.index', compact('settings', 'vendors'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'scope'            => ['required', 'in:global,vendor'],
            'scope_id'         => ['nullable', 'exists:vendors,id'],
            'applies_to'       => ['required', 'in:wholesale,retail,both'],
            'commission_type'  => ['required', 'in:percentage,fixed'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'note'             => ['nullable', 'string', 'max:255'],
        ]);

        WholesaleCommissionSetting::create($validated + ['is_active' => true]);

        return back()->with('success', 'Commission setting যোগ করা হয়েছে।');
    }

    public function update(Request $request, WholesaleCommissionSetting $setting)
    {
        $validated = $request->validate([
            'commission_type'  => ['required', 'in:percentage,fixed'],
            'commission_value' => ['required', 'numeric', 'min:0'],
            'is_active'        => ['boolean'],
            'note'             => ['nullable', 'string', 'max:255'],
        ]);

        $setting->update($validated);

        return back()->with('success', 'Commission setting আপডেট হয়েছে।');
    }

    public function destroy(WholesaleCommissionSetting $setting)
    {
        $setting->delete();
        return back()->with('success', 'Commission setting মুছে ফেলা হয়েছে।');
    }

    public function ledger(Request $request)
    {
        $vendorId = $request->get('vendor_id');

        $ledger = WholesaleCommissionLedger::with(['vendor', 'enquiry.product', 'order', 'settledBy'])
            ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
            ->latest()
            ->paginate(25);

        $vendors = Vendor::where('status', 'approved')->orderBy('shop_name')->get();

        $totals = [
            'total_sales'    => WholesaleCommissionLedger::sum('subtotal'),
            'total_commission' => WholesaleCommissionLedger::sum('commission_amount'),
            'settled'        => WholesaleCommissionLedger::where('settlement_status', 'settled')->sum('vendor_earning'),
            'pending'        => WholesaleCommissionLedger::where('settlement_status', 'pending')->sum('vendor_earning'),
        ];

        return view('admin.commission-settings.ledger', compact('ledger', 'vendors', 'totals'));
    }

    public function bulkSettle(Request $request)
    {
        $request->validate(['vendor_id' => ['required', 'exists:vendors,id']]);

        WholesaleCommissionLedger::where('vendor_id', $request->vendor_id)
            ->where('settlement_status', 'pending')
            ->update([
                'settlement_status' => 'settled',
                'settled_at'        => now(),
                'settled_by'        => Auth::id(),
            ]);

        return back()->with('success', 'সকল pending entry settle করা হয়েছে।');
    }

    public function settle(Request $request, WholesaleCommissionLedger $ledger)
    {
        $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        $ledger->update([
            'settlement_status' => 'settled',
            'settled_at'        => now(),
            'settled_by'        => Auth::id(),
            'admin_note'        => $request->admin_note,
        ]);

        return back()->with('success', 'Commission settled হিসেবে চিহ্নিত হয়েছে।');
    }
}
