<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesaleQuote;
use App\Models\WholesaleCommissionLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WholesaleQuoteController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $quotes = WholesaleQuote::with(['enquiry.product', 'enquiry.customer', 'vendor'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.wholesale-quotes.index', compact('quotes'));
    }

    public function show(WholesaleQuote $quote)
    {
        $quote->load(['enquiry.customer', 'enquiry.product', 'vendor', 'approvedBy']);

        return view('admin.wholesale-quotes.show', compact('quote'));
    }

    public function approve(Request $request, WholesaleQuote $quote)
    {
        $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        DB::transaction(function () use ($quote, $request) {
            $quote->update([
                'admin_approved'    => true,
                'admin_approved_at' => now(),
                'admin_approved_by' => Auth::id(),
                'admin_note'        => $request->admin_note,
                'status'            => 'approved',
            ]);

            // Create commission ledger entry
            $vendor   = $quote->vendor;
            $commData = $vendor->calculateWholesaleCommission((float) $quote->subtotal);

            WholesaleCommissionLedger::create([
                'vendor_id'                  => $quote->vendor_id,
                'customer_id'                => $quote->customer_id,
                'enquiry_id'                 => $quote->enquiry_id,
                'quote_id'                   => $quote->id,
                'order_type'                 => 'wholesale',
                'subtotal'                   => $quote->subtotal,
                'commission_type'            => $commData['commission_type'],
                'commission_value_snapshot'  => $commData['commission_value_snapshot'],
                'commission_amount'          => $commData['commission_amount'],
                'vendor_earning'             => $commData['vendor_earning'],
                'settlement_status'          => 'pending',
            ]);
        });

        return back()->with('success', 'Quote অনুমোদন করা হয়েছে। Customer দেখতে পারবেন।');
    }

    public function reject(Request $request, WholesaleQuote $quote)
    {
        $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        $quote->update([
            'admin_rejected_at' => now(),
            'admin_note'        => $request->admin_note,
            'status'            => 'rejected',
        ]);

        return back()->with('success', 'Quote প্রত্যাখ্যান করা হয়েছে।');
    }
}
