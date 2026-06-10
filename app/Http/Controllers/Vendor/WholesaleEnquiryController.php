<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleEnquiryController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor ?? abort(403);
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();
        $status = $request->get('status');

        $enquiries = WholesaleEnquiry::where('vendor_id', $vendor->id)
            ->when($status, fn($q) => $q->where('status', $status))
            ->with(['product', 'latestQuote'])
            ->withCount(['chatMessages as unread_count' => fn($q) => $q->where('sender_type', '!=', 'vendor')->where('is_read_by_vendor', false)])
            ->latest()
            ->paginate(20);

        return view('vendor.wholesale-enquiries.index', compact('enquiries', 'vendor'));
    }

    // Vendor sees enquiry without customer phone/email
    public function show(WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $enquiry->load(['product', 'quotes' => fn($q) => $q->where('vendor_id', $vendor->id)]);

        return view('vendor.wholesale-enquiries.show', compact('enquiry', 'vendor'));
    }

    public function decline(WholesaleEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);
        abort_unless($enquiry->status === 'pending', 422);

        $enquiry->update(['status' => 'rejected']);

        return back()->with('success', 'Enquiry প্রত্যাখ্যান করা হয়েছে।');
    }
}
