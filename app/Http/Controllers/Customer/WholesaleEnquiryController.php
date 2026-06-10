<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\WholesaleEnquiry;
use App\Notifications\EnquiryReceivedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WholesaleEnquiryController extends Controller
{
    public function index()
    {
        $customer  = Auth::user()->customer ?? abort(403);
        $enquiries = WholesaleEnquiry::where('customer_id', $customer->id)
            ->with(['product', 'vendor', 'customerVisibleQuote'])
            ->withCount(['chatMessages as unread_count' => fn($q) => $q->where('sender_type', '!=', 'customer')->where('is_read_by_customer', false)])
            ->latest()
            ->paginate(15);

        return view('customer.wholesale-enquiries.index', compact('enquiries', 'customer'));
    }

    public function show(WholesaleEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);

        $enquiry->load(['product', 'vendor', 'quotes.vendor', 'chatMessages']);

        return view('customer.wholesale-enquiries.show', compact('enquiry', 'customer'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => ['required', 'exists:products,id'],
            'quantity_kg'       => ['required', 'numeric', 'min:1'],
            'business_type'     => ['required', 'in:shop,restaurant,dealer,retailer,other'],
            'delivery_location' => ['required', 'string', 'max:255'],
            'customer_name'     => ['required', 'string', 'max:100'],
            'customer_phone'    => ['required', 'string', 'max:20'],
            'customer_whatsapp' => ['nullable', 'string', 'max:20'],
            'message'           => ['nullable', 'string', 'max:1000'],
            'redirect_to_chat'  => ['nullable', 'boolean'],
        ]);

        $customer = Auth::user()->customer ?? abort(403);
        $product  = Product::findOrFail($validated['product_id']);

        // Enforce the product's Minimum Order Quantity, if set.
        if ($product->min_order_quantity && (float) $validated['quantity_kg'] < (float) $product->min_order_quantity) {
            $unit = $product->min_order_unit ?: 'kg';
            $qty  = rtrim(rtrim(number_format((float) $product->min_order_quantity, 2, '.', ''), '0'), '.');

            return back()
                ->withInput()
                ->withErrors(['quantity_kg' => "এই পণ্যের জন্য সর্বনিম্ন অর্ডার পরিমাণ {$qty} {$unit}।"]);
        }

        $enquiry = WholesaleEnquiry::create([
            'customer_id'       => $customer->id,
            'product_id'        => $product->id,
            'vendor_id'         => $product->vendor_id,
            'quantity_kg'       => $validated['quantity_kg'],
            'business_type'     => $validated['business_type'],
            'delivery_location' => $validated['delivery_location'],
            'customer_name'     => $validated['customer_name'],
            'customer_phone'    => $validated['customer_phone'],
            'customer_whatsapp' => $validated['customer_whatsapp'] ?? null,
            'message'           => $validated['message'] ?? null,
            'product_name'      => $product->name_bn ?: $product->name_en,
            'status'            => 'pending',
        ]);

        Notify::admins(new EnquiryReceivedNotification($enquiry, 'admin'));
        Notify::vendor($product->vendor, new EnquiryReceivedNotification($enquiry, 'vendor'));
        Notify::customer($customer, new EnquiryReceivedNotification($enquiry, 'customer'));

        $msg = 'আপনার enquiry successfully submit হয়েছে। MoslaMart team / supplier quote পাঠাবে।';

        if ($request->boolean('redirect_to_chat')) {
            return redirect()->route('customer.wholesale.chat.show', $enquiry->id)
                ->with('success', $msg);
        }

        return redirect()->route('customer.wholesale.enquiry.index')
            ->with('success', $msg);
    }

    public function cancel(WholesaleEnquiry $enquiry)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($enquiry->customer_id === $customer->id, 403);
        abort_unless(in_array($enquiry->status, ['pending', 'quoted']), 422);

        $enquiry->update(['status' => 'cancelled']);

        return back()->with('success', 'Enquiry বাতিল করা হয়েছে।');
    }
}
