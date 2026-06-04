<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\PaykariComboEnquiry;
use App\Models\PaykariComboQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaykariComboEnquiryController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor ?? abort(403);
    }

    public function index()
    {
        $vendor    = $this->vendor();
        $enquiries = PaykariComboEnquiry::where('vendor_id', $vendor->id)
            ->with(['items'])
            ->latest()
            ->paginate(20);

        return view('vendor.paykari-combo.index', compact('enquiries', 'vendor'));
    }

    public function show(PaykariComboEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $enquiry->load(['items.product', 'quotes' => function ($q) use ($vendor) {
            $q->where('vendor_id', $vendor->id)->latest();
        }]);

        return view('vendor.paykari-combo.show', compact('enquiry', 'vendor'));
    }

    public function createQuote(PaykariComboEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted']), 422);

        $enquiry->load('items.product');

        return view('vendor.paykari-combo.quote', compact('enquiry', 'vendor'));
    }

    public function storeQuote(Request $request, PaykariComboEnquiry $enquiry)
    {
        $vendor = $this->vendor();
        abort_unless($enquiry->vendor_id === $vendor->id, 403);

        $validated = $request->validate([
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.product_name'   => ['required', 'string'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.quantity_kg'    => ['required', 'numeric', 'min:0'],
            'delivery_charge'        => ['required', 'numeric', 'min:0'],
            'advance_required'       => ['nullable', 'boolean'],
            'advance_amount'         => ['nullable', 'numeric', 'min:0'],
            'payment_options'        => ['nullable', 'array'],
            'payment_options.*'      => ['string', 'in:cod,bkash,nagad,bank,rocket'],
            'note'                   => ['nullable', 'string', 'max:1000'],
            'valid_until'            => ['nullable', 'date', 'after:today'],
        ]);

        $items = collect($validated['items'])->map(fn ($it) => [
            'product_id'   => (int) $it['product_id'],
            'product_name' => $it['product_name'],
            'unit_price'   => (float) $it['unit_price'],
            'quantity_kg'  => (float) $it['quantity_kg'],
            'subtotal'     => round((float) $it['unit_price'] * (float) $it['quantity_kg'], 2),
        ])->values()->all();

        PaykariComboQuote::create([
            'combo_enquiry_id' => $enquiry->id,
            'vendor_id'        => $vendor->id,
            'items'            => $items,
            'delivery_charge'  => $validated['delivery_charge'],
            'advance_required' => $request->boolean('advance_required'),
            'advance_amount'   => $validated['advance_amount'] ?? null,
            'payment_options'  => $validated['payment_options'] ?? [],
            'note'             => $validated['note'] ?? null,
            'valid_until'      => $validated['valid_until'] ?? null,
            'status'           => 'pending',
        ]);

        return redirect()->route('vendor.paykari-combo.show', $enquiry)
            ->with('success', 'Quote পাঠানো হয়েছে। Admin approval এর পরে customer দেখতে পাবে।');
    }
}
