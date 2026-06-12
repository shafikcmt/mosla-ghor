<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\PaykariComboEnquiry;
use App\Models\PaykariComboQuote;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaykariComboEnquiryController extends Controller
{
    public function index()
    {
        $customer  = Auth::user()->customer ?? abort(403);
        $enquiries = PaykariComboEnquiry::where('customer_id', $customer->id)
            ->with(['items', 'vendor', 'latestQuote'])
            ->latest()
            ->paginate(15);

        return view('customer.paykari-combo.index', compact('enquiries', 'customer'));
    }

    public function show(PaykariComboEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        $enquiry->load(['items.product', 'vendor', 'quotes.vendor']);

        return view('customer.paykari-combo.show', compact('enquiry', 'customer'));
    }

    public function store(Request $request)
    {
        // Public: guest or logged-in. No forced login.
        $customer = (Auth::check() && Auth::user()->role === 'customer')
            ? Auth::user()->customer
            : null;

        $validated = $request->validate([
            'items'                  => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id'     => ['required', 'exists:products,id'],
            'items.*.quantity_kg'    => ['required', 'numeric', 'min:0.1'],
            'items.*.quantity_unit'  => ['nullable', 'string', 'in:kg,bag,carton,piece'],
            'delivery_location'      => ['required', 'string', 'max:255'],
            'business_type'          => ['nullable', 'in:shop,restaurant,dealer,retailer,other'],
            'customer_name'          => ['required', 'string', 'max:100'],
            'customer_phone'         => ['required', 'string', 'max:20', 'regex:/^[0-9+\-\s]{6,20}$/'],
            'customer_whatsapp'      => ['nullable', 'string', 'max:20'],
            'message'                => ['nullable', 'string', 'max:1000'],
        ], [
            'customer_phone.regex' => 'সঠিক ফোন নম্বর দিন।',
        ]);

        $enquiry = PaykariComboEnquiry::create([
            'customer_id'       => $customer?->id,
            'customer_name'     => $validated['customer_name'],
            'customer_phone'    => $validated['customer_phone'],
            'customer_whatsapp' => $validated['customer_whatsapp'] ?? null,
            'delivery_location' => $validated['delivery_location'],
            'business_type'     => $validated['business_type'] ?? 'other',
            'message'           => $validated['message'] ?? null,
            'status'            => 'pending',
        ]);

        foreach ($validated['items'] as $itemData) {
            $product = Product::find($itemData['product_id']);
            $enquiry->items()->create([
                'product_id'    => $itemData['product_id'],
                'product_name'  => $product ? ($product->name_bn ?: $product->name_en) : 'N/A',
                'quantity_kg'   => $itemData['quantity_kg'],
                'quantity_unit' => $itemData['quantity_unit'] ?? 'kg',
            ]);
        }

        $msg = 'আপনার পাইকারি কম্বো enquiry successfully submit হয়েছে। MoslaMart team / supplier quote পাঠাবে।';

        // Logged-in customers land on their enquiry list; guests return home with the flash.
        if ($customer) {
            return redirect()->route('customer.paykari-combo.index')->with('success', $msg);
        }

        return redirect('/')->with('success', $msg . ' আপনি চাইলে পরে একই ফোন নম্বরে account তৈরি করে status দেখতে পারবেন।');
    }

    public function cancel(PaykariComboEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted']), 422);

        $enquiry->update(['status' => 'cancelled']);

        return back()->with('success', 'Enquiry বাতিল করা হয়েছে।');
    }

    public function acceptQuote(PaykariComboEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        $quote = $enquiry->approvedQuote;
        abort_unless($quote, 404);

        $quote->update(['customer_response' => 'accepted']);
        $enquiry->update(['status' => 'accepted']);

        return back()->with('success', 'Quote গ্রহণ করা হয়েছে। Vendor শীঘ্রই যোগাযোগ করবে।');
    }

    public function declineQuote(PaykariComboEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        $quote = $enquiry->approvedQuote;
        abort_unless($quote, 404);

        $quote->update(['customer_response' => 'declined']);

        return back()->with('success', 'Quote প্রত্যাখ্যান করা হয়েছে।');
    }
}
