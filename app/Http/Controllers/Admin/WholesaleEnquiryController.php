<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\WholesaleEnquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    /** Delete one enquiry (e.g. a test entry) with its quotes and chat. */
    public function destroy(WholesaleEnquiry $enquiry)
    {
        if (! $this->deletable($enquiry)) {
            return back()->with('error', "Enquiry #{$enquiry->id} থেকে অর্ডার / কমিশন হয়েছে — রেকর্ড রক্ষায় এটি মুছা যাবে না।");
        }

        $id = $enquiry->id;
        $this->purge($enquiry);

        return redirect()->route('admin.wholesale.enquiry.index')->with('success', "Enquiry #{$id} মুছে ফেলা হয়েছে।");
    }

    /** Delete the ticked enquiries; ones that already became an order are kept. */
    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids'   => ['required', 'array', 'max:100'],
            'ids.*' => ['integer'],
        ], ['ids.required' => 'অন্তত একটি enquiry বেছে নিন।']);

        $deleted = 0;
        $kept    = [];
        foreach (WholesaleEnquiry::whereIn('id', $data['ids'])->get() as $enquiry) {
            if ($this->deletable($enquiry)) {
                $this->purge($enquiry);
                $deleted++;
            } else {
                $kept[] = '#'.$enquiry->id;
            }
        }

        $msg = "{$deleted}টি enquiry মুছে ফেলা হয়েছে।";
        if ($kept) {
            $msg .= ' অর্ডার হয়ে যাওয়ায় রাখা হয়েছে: '.implode(', ', $kept).'।';
        }

        return back()->with($deleted ? 'success' : 'error', $msg);
    }

    /** Orders / commission rows are financial records — never orphan them. */
    private function deletable(WholesaleEnquiry $enquiry): bool
    {
        return ! $enquiry->orders()->exists()
            && ! DB::table('wholesale_commission_ledger')->where('enquiry_id', $enquiry->id)->exists();
    }

    private function purge(WholesaleEnquiry $enquiry): void
    {
        DB::transaction(function () use ($enquiry) {
            // Explicit (not only FK cascade) so it also holds where FKs are off.
            $enquiry->chatMessages()->delete();
            $enquiry->quotes()->delete();
            $enquiry->delete();
        });
    }
}
