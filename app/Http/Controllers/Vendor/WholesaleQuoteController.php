<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;
use App\Models\WholesaleChatMessage;
use App\Notifications\QuoteSubmittedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleQuoteController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor ?? abort(403);
    }

    public function create(WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted']), 422);

        $enquiry->load('product');

        return view('vendor.wholesale-quotes.create', compact('enquiry', 'vendor'));
    }

    public function store(Request $request, WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $quote = WholesaleQuote::createFromRequest($request, $enquiry, $vendor->id);

        $enquiry->update(['status' => 'quoted']);

        // System chat line — customer sees the quote immediately (no admin approval).
        WholesaleChatMessage::create([
            'enquiry_id'  => $enquiry->id,
            'quote_id'    => $quote->id,
            'sender_type' => 'vendor',
            'sender_id'   => $vendor->id,
            'message'     => 'নতুন কোটেশন পাঠানো হয়েছে — দেখে order confirm করতে পারেন।',
        ]);

        Notify::customer($enquiry->customer, new QuoteSubmittedNotification($quote, 'customer'));
        Notify::admins(new QuoteSubmittedNotification($quote, 'admin'));

        return redirect()->route('vendor.wholesale.enquiry.show', $enquiry->id)
            ->with('success', 'কোটেশন পাঠানো হয়েছে। Customer সরাসরি দেখতে পারবে।');
    }

    public function index()
    {
        $vendor = $this->vendor();
        $quotes = WholesaleQuote::where('vendor_id', $vendor->id)
            ->with(['enquiry.product', 'enquiry.customer'])
            ->latest()
            ->paginate(20);

        return view('vendor.wholesale-quotes.index', compact('quotes', 'vendor'));
    }

    public function show(WholesaleQuote $quote)
    {
        $vendor = $this->vendor();
        abort_unless($quote->vendor_id === $vendor->id, 403);

        $quote->load(['enquiry.product']);

        return view('vendor.wholesale-quotes.show', compact('quote', 'vendor'));
    }
}
