<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\WholesaleEnquiry;
use Illuminate\Http\Request;

class WholesaleEnquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $enquiries = WholesaleEnquiry::with(['customer', 'product', 'vendor', 'latestQuote'])
            ->withCount(['chatMessages as unread_count' => fn($q) => $q->where('sender_type', '!=', 'admin')->where('is_read_by_admin', false)])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.wholesale-enquiries.index', compact('enquiries'));
    }

    // Admin sees full customer contact info
    public function show(WholesaleEnquiry $enquiry)
    {
        $enquiry->load(['customer', 'product', 'variant', 'vendor', 'quotes.vendor', 'chatMessages']);

        $vendors = Vendor::where('status', 'approved')->orderBy('shop_name')->get();

        return view('admin.wholesale-enquiries.show', compact('enquiry', 'vendors'));
    }

    // Manually assign / reassign the enquiry to a supplier (or back to admin-only).
    public function assign(Request $request, WholesaleEnquiry $enquiry)
    {
        $validated = $request->validate([
            'vendor_id' => ['nullable', 'exists:vendors,id'],
        ]);

        $enquiry->update(['vendor_id' => $validated['vendor_id'] ?: null]);

        if ($enquiry->vendor_id) {
            \App\Support\Notify::vendor($enquiry->vendor, new \App\Notifications\EnquiryReceivedNotification($enquiry, 'vendor'));
        }

        return back()->with('success', 'Enquiry অ্যাসাইন করা হয়েছে।');
    }

    public function updateStatus(Request $request, WholesaleEnquiry $enquiry)
    {
        $request->validate([
            'status'     => ['required', 'in:pending,quoted,accepted,completed,rejected,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $enquiry->update([
            'status'     => $request->status,
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'Enquiry status আপডেট হয়েছে।');
    }
}
