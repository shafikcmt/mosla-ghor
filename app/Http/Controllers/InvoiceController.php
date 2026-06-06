<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\WebsiteSetting;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\PaymentUpdatedNotification;
use App\Support\Notify;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Public, token-addressed invoice for vendor-created (POS) orders.
 * No auth — the secret 40-char token IS the access control. Every action
 * re-checks {@see Order::isInvoiceActive()} so an admin/vendor can revoke a link.
 */
class InvoiceController extends Controller
{
    /** Resolve an order by its public invoice token or 404. */
    private function resolve(string $token): Order
    {
        $order = Order::where('invoice_token', $token)->first();
        if (! $order || ! $order->isInvoiceActive()) {
            abort(404);
        }
        return $order;
    }

    private function siteName(): string
    {
        return WebsiteSetting::get('site_name', 'মসলা মার্ট');
    }

    public function show(string $token)
    {
        $order = $this->resolve($token);
        $order->load(['items', 'vendorCustomer', 'createdByVendor']);

        return view('invoice.show', [
            'order'    => $order,
            'vendor'   => $order->createdByVendor,
            'siteName' => $this->siteName(),
        ]);
    }

    public function reorder(string $token)
    {
        $order = $this->resolve($token);
        $order->load(['items', 'createdByVendor']);

        return view('invoice.reorder', [
            'order'    => $order,
            'vendor'   => $order->createdByVendor,
            'siteName' => $this->siteName(),
        ]);
    }

    /** Duplicate the order as a NEW pending request to the same vendor. */
    public function reorderStore(string $token)
    {
        $order = $this->resolve($token);
        $order->load('items');

        if ($order->items->isEmpty()) {
            return back()->with('error', 'এই অর্ডারে কোনো পণ্য নেই।');
        }

        do {
            $orderNumber = 'POS-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        $new = DB::transaction(function () use ($order, $orderNumber) {
            $subtotal = 0.0;
            $new = Order::create([
                'order_number'         => $orderNumber,
                'customer_name'        => $order->customer_name,
                'mobile_number'        => $order->mobile_number,
                'full_address'         => $order->full_address,
                'order_note'           => 'পুনঃঅর্ডার — মূল #' . $order->order_number,
                'order_type'           => $order->order_type ?: 'retail',
                'order_source'         => 'vendor_created_order',
                'created_by_vendor_id' => $order->created_by_vendor_id,
                'vendor_customer_id'   => $order->vendor_customer_id,
                'subtotal'             => 0,
                'discount_amount'      => 0,
                'packaging_cost'       => 0,
                'delivery_charge'      => 0,
                'grand_total'          => 0,
                'payment_method'       => 'cash',
                'paid_amount'          => 0,
                'partial_paid_amount'  => 0,
                'due_amount'           => 0,
                'payment_status'       => 'pending',
                'order_status'         => 'pending',   // vendor must confirm + fulfil
            ]);

            foreach ($order->items as $it) {
                $lineTotal = max(0, round((float) ($it->quantity ?? 0) * (float) $it->unit_price - (float) $it->discount_amount, 2));
                $subtotal += $lineTotal;
                $new->items()->create([
                    'vendor_id'       => $it->vendor_id,
                    'vendor_name'     => $it->vendor_name,
                    'product_id'      => $it->product_id,
                    'product_name'    => $it->product_name,
                    'quantity'        => $it->quantity,
                    'unit'            => $it->unit,
                    'unit_price'      => $it->unit_price,
                    'discount_amount' => $it->discount_amount,
                    'line_total'      => $lineTotal,
                ]);
            }

            $new->update([
                'subtotal'    => $subtotal,
                'grand_total' => $subtotal,
                'due_amount'  => $subtotal,
            ]);
            $new->ensureTokens();

            return $new;
        });

        // Tell the vendor (+ admins) a customer placed a reorder.
        $new->loadMissing('createdByVendor');
        Notify::vendor($new->createdByVendor, new OrderPlacedNotification($new, 'vendor'));
        Notify::admins(new OrderPlacedNotification($new, 'admin'));

        return view('invoice.reorder-done', [
            'order'    => $new,
            'siteName' => $this->siteName(),
        ]);
    }

    public function pay(string $token)
    {
        $order = $this->resolve($token);

        if ($order->due_amount <= 0) {
            return view('invoice.pay-done', [
                'order'    => $order,
                'siteName' => $this->siteName(),
                'already'  => true,
            ]);
        }

        return view('invoice.pay', [
            'order'    => $order,
            'siteName' => $this->siteName(),
        ]);
    }

    /** Record a customer payment claim (vendor reconciles later). */
    public function payStore(Request $request, string $token)
    {
        $order = $this->resolve($token);

        $data = $request->validate([
            'payment_method' => 'required|string|max:30',
            'sender_number'  => 'nullable|string|max:30',
            'transaction_id' => 'nullable|string|max:80',
            'amount'         => 'nullable|numeric|min:0',
        ]);

        $note = trim(($order->order_note ? $order->order_note . "\n" : '')
            . 'পেমেন্ট দাবি: ' . $data['payment_method']
            . ($data['amount'] ? ' — ৳' . $data['amount'] : '')
            . ($data['transaction_id'] ? ' — TxID ' . $data['transaction_id'] : '')
            . ($data['sender_number'] ? ' — ' . $data['sender_number'] : ''));

        $order->update([
            'payment_method'       => $data['payment_method'],
            'sender_number'        => $data['sender_number'] ?? $order->sender_number,
            'transaction_id'       => $data['transaction_id'] ?? $order->transaction_id,
            'order_note'           => $note,
            'customer_confirmed_at' => now(),
        ]);

        // Notify the vendor (+ admins) that the customer reported a payment.
        $order->loadMissing('createdByVendor');
        Notify::vendor($order->createdByVendor, new PaymentUpdatedNotification($order, 'partial'));
        Notify::admins(new PaymentUpdatedNotification($order, 'partial'));

        return view('invoice.pay-done', [
            'order'    => $order,
            'siteName' => $this->siteName(),
            'already'  => false,
        ]);
    }
}
