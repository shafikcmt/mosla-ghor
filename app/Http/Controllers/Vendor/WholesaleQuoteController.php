<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\WholesaleEnquiry;
use App\Models\WholesaleQuote;
use App\Models\WholesaleChatMessage;
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

        $validated = $request->validate([
            'unit_price'       => ['required', 'numeric', 'min:0'],
            'quantity'         => ['required', 'numeric', 'min:0.5'],
            'quantity_unit'    => ['required', 'string', 'in:kg,ton,piece,bag'],
            'delivery_charge'  => ['required', 'numeric', 'min:0'],
            'advance_required' => ['nullable', 'numeric', 'min:0'],
            'payment_options'  => ['nullable', 'array'],
            'payment_options.*'=> ['in:online,manual,cod,partial'],
            'note'             => ['nullable', 'string', 'max:1000'],
            'valid_until'      => ['nullable', 'date', 'after:today'],
        ]);

        $subtotal = round($validated['unit_price'] * $validated['quantity'], 2);

        WholesaleQuote::create([
            'enquiry_id'       => $enquiry->id,
            'vendor_id'        => $vendor->id,
            'customer_id'      => $enquiry->customer_id,
            'unit_price'       => $validated['unit_price'],
            'quantity'         => $validated['quantity'],
            'quantity_unit'    => $validated['quantity_unit'],
            'subtotal'         => $subtotal,
            'delivery_charge'  => $validated['delivery_charge'],
            'advance_required' => $validated['advance_required'] ?? 0,
            'payment_options'  => $validated['payment_options'] ?? [],
            'note'             => $validated['note'] ?? null,
            'valid_until'      => $validated['valid_until'] ?? null,
            'status'           => 'pending', // awaiting admin approval
            'admin_approved'   => false,
        ]);

        $enquiry->update(['status' => 'quoted']);

        // Post a system chat message notifying customer that a quote has been sent
        WholesaleChatMessage::create([
            'enquiry_id'  => $enquiry->id,
            'sender_type' => 'vendor',
            'sender_id'   => $vendor->id,
            'message'     => 'নতুন কোটেশন পাঠানো হয়েছে। Admin অনুমোদনের পরে আপনি দেখতে পারবেন।',
        ]);

        return redirect()->route('vendor.wholesale.enquiry.show', $enquiry->id)
            ->with('success', 'কোটেশন পাঠানো হয়েছে। Admin অনুমোদনের জন্য অপেক্ষা করুন।');
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
