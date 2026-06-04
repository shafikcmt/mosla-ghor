<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\WholesaleQuote;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleCommissionLedger;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WholesaleQuoteController extends Controller
{
    public function index()
    {
        $customer = Auth::user()->customer ?? abort(403);
        $quotes   = WholesaleQuote::where('customer_id', $customer->id)
            ->where('admin_approved', true)
            ->with(['enquiry.product', 'vendor'])
            ->latest()
            ->paginate(15);

        return view('customer.wholesale-quotes.index', compact('quotes', 'customer'));
    }

    public function show(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless($quote->admin_approved, 403);

        $quote->load(['enquiry.product', 'vendor']);

        return view('customer.wholesale-quotes.show', compact('quote', 'customer'));
    }

    public function accept(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless($quote->admin_approved && $quote->status === 'approved', 403);

        DB::transaction(function () use ($quote, $customer) {
            $quote->update(['status' => 'accepted']);
            $quote->enquiry->update(['status' => 'accepted']);
        });

        return redirect()->route('customer.wholesale.enquiry.show', $quote->enquiry_id)
            ->with('success', 'Quote গ্রহণ করা হয়েছে! Admin payment নিশ্চিত করার পরে order তৈরি হবে।');
    }

    public function reject(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless(in_array($quote->status, ['pending', 'approved']), 403);

        $quote->update(['status' => 'rejected']);

        return back()->with('success', 'Quote প্রত্যাখ্যান করা হয়েছে।');
    }
}
