<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleChatMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleChatController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor ?? abort(403);
    }

    public function show(WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $enquiry->chatMessages()
            ->where('sender_type', '!=', 'vendor')
            ->where('is_read_by_vendor', false)
            ->update(['is_read_by_vendor' => true]);

        $messages = $enquiry->chatMessages()->get();
        $enquiry->load(['product', 'latestQuote']);

        return view('vendor.wholesale-chat.show', compact('enquiry', 'messages', 'vendor'));
    }

    public function store(Request $request, WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted', 'accepted']), 422);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $filterReason = WholesaleChatMessage::detectContactSharing($validated['message']);

        WholesaleChatMessage::create([
            'enquiry_id'       => $enquiry->id,
            'sender_type'      => 'vendor',
            'sender_id'        => $vendor->id,
            'message'          => $validated['message'],
            'is_filtered'      => $filterReason !== null,
            'filter_reason'    => $filterReason,
            'is_read_by_customer' => false,
            'is_read_by_admin'    => false,
        ]);

        if ($filterReason) {
            return back()->with('info', 'Customer enquiry এবং quote process সুন্দরভাবে manage করার জন্য MoslaMart chatbox ব্যবহার করুন। এতে Admin, Vendor এবং Customer—সবার জন্য order record, quote history এবং payment tracking সহজ হবে।');
        }

        return back()->with('success', 'বার্তা পাঠানো হয়েছে।');
    }

    public function unread(WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $count = $enquiry->chatMessages()
            ->where('sender_type', '!=', 'vendor')
            ->where('is_read_by_vendor', false)
            ->count();

        return response()->json(['unread' => $count]);
    }
}
