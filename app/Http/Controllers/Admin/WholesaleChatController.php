<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleChatMessage;
use App\Notifications\EnquiryChatMessageNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleChatController extends Controller
{
    public function index()
    {
        $enquiries = WholesaleEnquiry::withCount([
                'chatMessages as unread_count' => fn($q) => $q->where('is_read_by_admin', false),
            ])
            ->with(['customer', 'vendor', 'product'])
            ->latest()
            ->paginate(25);

        return view('admin.wholesale-chat.index', compact('enquiries'));
    }

    public function show(WholesaleEnquiry $enquiry)
    {
        $enquiry->chatMessages()->where('is_read_by_admin', false)->update(['is_read_by_admin' => true]);

        $messages = $enquiry->chatMessages()->get();
        $enquiry->load(['customer', 'vendor', 'product']);

        return view('admin.wholesale-chat.show', compact('enquiry', 'messages'));
    }

    // Admin can send a message in any thread
    public function store(Request $request, WholesaleEnquiry $enquiry)
    {
        $validated = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        WholesaleChatMessage::create([
            'enquiry_id'          => $enquiry->id,
            'sender_type'         => 'admin',
            'sender_id'           => Auth::id(),
            'message'             => $validated['message'],
            'is_read_by_customer' => false,
            'is_read_by_vendor'   => false,
        ]);

        // Notify the other parties (supplier + customer).
        Notify::vendor($enquiry->vendor, new EnquiryChatMessageNotification($enquiry, 'vendor'));
        Notify::customer($enquiry->customer, new EnquiryChatMessageNotification($enquiry, 'customer'));

        return back()->with('success', 'বার্তা পাঠানো হয়েছে।');
    }
}
