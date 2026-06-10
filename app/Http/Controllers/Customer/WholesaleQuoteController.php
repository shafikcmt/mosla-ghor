<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\WholesaleCommissionLedger;
use App\Models\WholesaleQuote;
use App\Notifications\EnquiryOrderConfirmedNotification;
use App\Support\Notify;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class WholesaleQuoteController extends Controller
{
    public function index()
    {
        $customer = Auth::user()->customer ?? abort(403);
        $quotes   = WholesaleQuote::where('customer_id', $customer->id)
            ->whereIn('status', WholesaleQuote::CUSTOMER_VISIBLE_STATUSES)
            ->with(['enquiry.product', 'vendor'])
            ->latest()
            ->paginate(15);

        return view('customer.wholesale-quotes.index', compact('quotes', 'customer'));
    }

    public function show(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless($quote->isVisibleToCustomer(), 403);

        // The full quote card + Order Confirm action live on the enquiry detail page.
        return redirect()->route('customer.wholesale.enquiry.show', $quote->enquiry_id);
    }

    // Customer confirms the quote → an order is created automatically (no admin step).
    public function confirmOrder(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless($quote->status === 'sent_to_customer', 403);

        if ($quote->isExpired()) {
            return back()->with('warning', 'এই কোটেশনের মেয়াদ শেষ হয়ে গেছে। Supplier-কে নতুন quote দিতে বলুন।');
        }

        $enquiry = $quote->enquiry;

        $order = DB::transaction(function () use ($quote, $enquiry, $customer) {
            do {
                $orderNumber = 'MSL-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            } while (Order::where('order_number', $orderNumber)->exists());

            $location = $enquiry->delivery_location ?: '—';

            $order = Order::create([
                'order_number'    => $orderNumber,
                'customer_name'   => $enquiry->customer_name ?: $customer->name,
                'mobile_number'   => $enquiry->customer_phone ?: $customer->mobile_number,
                'full_address'    => $location,
                'district'        => Str::limit($location, 78, ''),
                'area'            => Str::limit($location, 78, ''),
                'order_type'      => 'wholesale',
                'subtotal'        => $quote->subtotal,
                'delivery_charge' => $quote->delivery_charge,
                'grand_total'     => $quote->grandTotal(),
                'payment_method'  => 'manual',
                'payment_status'  => 'pending',
                'order_status'    => 'pending',
                'customer_id'     => $customer->id,
                'enquiry_id'      => $enquiry->id,
                'order_note'      => 'পাইকারি Enquiry #' . $enquiry->id . ' থেকে তৈরি।',
            ]);

            $order->items()->create([
                'vendor_id'    => $quote->vendor_id,
                'vendor_name'  => $quote->vendor?->shop_name,
                'product_id'   => $enquiry->product_id,
                'product_name' => $enquiry->product_name,
                'quantity'     => $quote->quantity,
                'unit'         => $quote->quantity_unit,
                'unit_price'   => $quote->unit_price,
                'line_total'   => $quote->subtotal,
            ]);

            $quote->update(['status' => 'converted_to_order', 'order_id' => $order->id]);
            $enquiry->update(['status' => 'accepted']);

            // Commission ledger only when a supplier is involved.
            if ($quote->vendor_id && $quote->vendor) {
                $commData = $quote->vendor->calculateWholesaleCommission((float) $quote->subtotal);
                WholesaleCommissionLedger::create([
                    'vendor_id'                 => $quote->vendor_id,
                    'customer_id'               => $quote->customer_id,
                    'enquiry_id'                => $quote->enquiry_id,
                    'quote_id'                  => $quote->id,
                    'order_type'                => 'wholesale',
                    'subtotal'                  => $quote->subtotal,
                    'commission_type'           => $commData['commission_type'],
                    'commission_value_snapshot' => $commData['commission_value_snapshot'],
                    'commission_amount'         => $commData['commission_amount'],
                    'vendor_earning'            => $commData['vendor_earning'],
                    'settlement_status'         => 'pending',
                ]);
            }

            return $order;
        });

        Notify::customer($customer, new EnquiryOrderConfirmedNotification($order, 'customer'));
        Notify::admins(new EnquiryOrderConfirmedNotification($order, 'admin'));
        Notify::vendor($quote->vendor, new EnquiryOrderConfirmedNotification($order, 'vendor'));

        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'অভিনন্দন! আপনার order confirm হয়েছে। নিচে order বিস্তারিত দেখুন।');
    }

    public function reject(WholesaleQuote $quote)
    {
        $customer = Auth::user()->customer ?? abort(403);
        abort_unless($quote->customer_id === $customer->id, 403);
        abort_unless($quote->status === 'sent_to_customer', 403);

        $quote->update(['status' => 'rejected']);

        return back()->with('success', 'Quote প্রত্যাখ্যান করা হয়েছে।');
    }
}
