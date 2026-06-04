<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use Illuminate\Http\Request;

class WholesaleEnquiryController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $enquiries = WholesaleEnquiry::with(['customer', 'product', 'vendor', 'latestQuote'])
            ->when($status, fn($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('admin.wholesale-enquiries.index', compact('enquiries'));
    }

    // Admin sees full customer contact info
    public function show(WholesaleEnquiry $enquiry)
    {
        $enquiry->load(['customer', 'product', 'vendor', 'quotes.vendor', 'chatMessages']);

        return view('admin.wholesale-enquiries.show', compact('enquiry'));
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
