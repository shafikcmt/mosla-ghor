<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaykariComboEnquiry;
use App\Models\PaykariComboQuote;
use App\Models\Vendor;
use Illuminate\Http\Request;

class PaykariComboEnquiryController extends Controller
{
    public function index()
    {
        $enquiries = PaykariComboEnquiry::with(['items', 'vendor'])
            ->latest()
            ->paginate(20);

        return view('admin.paykari-combo.index', compact('enquiries'));
    }

    public function show(PaykariComboEnquiry $enquiry)
    {
        $enquiry->load(['items.product', 'vendor', 'quotes.vendor', 'customer']);
        $vendors = Vendor::where('is_approved', true)->orderBy('business_name')->get();

        return view('admin.paykari-combo.show', compact('enquiry', 'vendors'));
    }

    public function updateStatus(Request $request, PaykariComboEnquiry $enquiry)
    {
        $request->validate([
            'status'     => ['required', 'in:pending,quoted,accepted,completed,rejected,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'vendor_id'  => ['nullable', 'exists:vendors,id'],
        ]);

        $data = ['status' => $request->status];
        if ($request->filled('admin_note')) $data['admin_note'] = $request->admin_note;
        if ($request->filled('vendor_id'))  $data['vendor_id']  = $request->vendor_id;

        $enquiry->update($data);

        return back()->with('success', 'Enquiry আপডেট করা হয়েছে।');
    }

    public function approveQuote(Request $request, PaykariComboQuote $quote)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $quote->update([
            'admin_approved' => true,
            'status'         => 'approved',
            'admin_note'     => $request->admin_note,
        ]);

        $quote->enquiry->update(['status' => 'quoted']);

        return back()->with('success', 'Quote approve করা হয়েছে। Customer দেখতে পাবে।');
    }

    public function rejectQuote(Request $request, PaykariComboQuote $quote)
    {
        $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $quote->update([
            'status'     => 'rejected',
            'admin_note' => $request->admin_note,
        ]);

        return back()->with('success', 'Quote reject করা হয়েছে।');
    }
}
