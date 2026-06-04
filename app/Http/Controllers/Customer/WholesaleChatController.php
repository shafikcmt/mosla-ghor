<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleChatController extends Controller
{
    public function show(WholesaleEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        // Mark vendor messages as read by customer
        $enquiry->chatMessages()
            ->where('sender_type', '!=', 'customer')
            ->where('is_read_by_customer', false)
            ->update(['is_read_by_customer' => true]);

        $messages = $enquiry->chatMessages()->with([])->get();
        $enquiry->load(['product', 'vendor', 'latestQuote']);

        return view('customer.wholesale-chat.show', compact('enquiry', 'messages', 'customer'));
    }

    public function store(Request $request, WholesaleEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted', 'accepted']), 422);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $filterReason = WholesaleChatMessage::detectContactSharing($validated['message']);

        WholesaleChatMessage::create([
            'enquiry_id'        => $enquiry->id,
            'sender_type'       => 'customer',
            'sender_id'         => $customer->id,
            'message'           => $validated['message'],
            'is_filtered'       => $filterReason !== null,
            'filter_reason'     => $filterReason,
            'is_read_by_vendor' => false,
            'is_read_by_admin'  => false,
        ]);

        if ($filterReason) {
            return back()->with('warning', 'আপনার অর্ডার, quote এবং payment record নিরাপদে রাখার জন্য MoslaMart-এর ভিতরেই chat এবং order process complete করুন।');
        }

        return back()->with('success', 'বার্তা পাঠানো হয়েছে।');
    }

    // Polling endpoint — returns unread message count for this customer
    public function unread(WholesaleEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        $count = $enquiry->chatMessages()
            ->where('sender_type', '!=', 'customer')
            ->where('is_read_by_customer', false)
            ->count();

        return response()->json(['unread' => $count]);
    }
}
