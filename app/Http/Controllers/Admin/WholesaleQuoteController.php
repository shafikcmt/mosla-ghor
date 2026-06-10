<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;
use App\Models\WholesaleChatMessage;
use App\Notifications\QuoteSubmittedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;

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
        $quote->load(['enquiry.customer', 'enquiry.product', 'vendor']);

        return view('admin.wholesale-quotes.show', compact('quote'));
    }

    // Admin submits a quote directly to the customer (e.g. when no vendor is assigned).
    public function create(WholesaleEnquiry $enquiry)
    {
        abort_unless(in_array($enquiry->status, ['pending', 'quoted']), 422);
        $enquiry->load('product');

        return view('admin.wholesale-quotes.create', compact('enquiry'));
    }

    public function store(Request $request, WholesaleEnquiry $enquiry)
    {
        $quote = WholesaleQuote::createFromRequest($request, $enquiry, $enquiry->vendor_id);

        $enquiry->update(['status' => 'quoted']);

        WholesaleChatMessage::create([
            'enquiry_id'  => $enquiry->id,
            'quote_id'    => $quote->id,
            'sender_type' => 'admin',
            'sender_id'   => auth()->id(),
            'message'     => 'নতুন কোটেশন পাঠানো হয়েছে — দেখে order confirm করতে পারেন।',
        ]);

        Notify::customer($enquiry->customer, new QuoteSubmittedNotification($quote, 'customer'));

        return redirect()->route('admin.wholesale.enquiry.show', $enquiry->id)
            ->with('success', 'কোটেশন পাঠানো হয়েছে। Customer সরাসরি দেখতে পারবে।');
    }
}
