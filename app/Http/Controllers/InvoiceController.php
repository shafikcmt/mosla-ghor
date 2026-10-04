<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\WebsiteSetting;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\PaymentUpdatedNotification;
use App\Support\Notify;
use App\Models\Customer;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    /** Real (server-generated) PDF of the order invoice — same data as show(). */
    public function pdf(string $token)
    {
        $order = $this->resolve($token);
        $order->load(['items', 'createdByVendor']);

        $pdf = \App\Support\OrderInvoicePdf::bytes($order);

        return response($pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="invoice-' . $order->order_number . '.pdf"');
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
                'full_address'         => $order->full_address ?? '',
                'district'             => $order->district ?? '',
                'area'                 => $order->area ?? '',
                'order_note'           => 'পুনঃঅর্ডার — মূল #' . $order->order_number,
                'order_type'           => $order->order_type ?: 'retail',
                // Vendor POS reorders go back to the vendor; everything else to admin.
                'order_source'         => $order->created_by_vendor_id ? 'vendor_created_order' : 'admin_manual_order',
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

        if ($order->effectiveDue() <= 0) {
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

    // ── Customer account from the invoice link ───────────────────────────────
    // Orders taken over phone/WhatsApp often have a phone number only. The
    // invoice message links here: a customer WITHOUT an account adds their
    // address/info and sets a password (account created, order attached);
    // one who already has an account is sent to login instead.

    public function account(string $token)
    {
        $order = $this->resolve($token);

        if ($redirect = $this->accountRedirect($order)) {
            return $redirect;
        }

        $order->load('items');

        return view('invoice.account', [
            'order'    => $order,
            'phone'    => Phone::normalize($order->mobile_number),
            'siteName' => $this->siteName(),
        ]);
    }

    public function accountStore(Request $request, string $token)
    {
        $order = $this->resolve($token);

        if ($redirect = $this->accountRedirect($order)) {
            return $redirect;
        }

        $phone = Phone::normalize($order->mobile_number);
        abort_unless($phone, 404);

        $data = $request->validate([
            'name'               => 'required|string|max:100',
            'full_address'       => 'required|string|max:1000',
            'district'           => 'nullable|string|max:80',
            'area'               => 'nullable|string|max:80',
            'alternative_number' => 'nullable|string|max:20',
            'email'              => 'nullable|email|max:150|unique:users,email',
            'password'           => 'required|string|min:6|confirmed',
        ], [
            'name.required'         => 'আপনার নাম লিখুন।',
            'full_address.required' => 'ডেলিভারির ঠিকানা লিখুন।',
            'email.unique'          => 'এই ইমেইল দিয়ে আগেই একটি অ্যাকাউন্ট আছে।',
            'password.required'     => 'পাসওয়ার্ড দিন।',
            'password.min'          => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।',
            'password.confirmed'    => 'পাসওয়ার্ড মিলছে না।',
        ]);

        $user = DB::transaction(function () use ($data, $phone, $order) {
            $user = User::create([
                'name'            => $data['name'],
                'email'           => $data['email'] ?? null,
                'phone'           => $phone,
                'password'        => Hash::make($data['password']),
                'password_set_at' => now(),
                'role'            => 'customer',
                'is_admin'        => false,
            ]);

            $customer = Customer::firstOrNew(['mobile_number' => $phone]);
            $customer->fill([
                'name'               => $data['name'],
                'email'              => $customer->email ?: ($data['email'] ?? null),
                'alternative_number' => $data['alternative_number'] ?? $customer->alternative_number,
                'last_full_address'  => $data['full_address'],
                'last_district_name' => $data['district'] ?? $customer->last_district_name,
                'is_active'          => true,
            ])->save();

            $order->update([
                'customer_id'           => $customer->id,
                'customer_name'         => $data['name'],
                'mobile_number'         => $phone,
                'full_address'          => $data['full_address'],
                'district'              => $data['district'] ?? ($order->district ?? ''),
                'area'                  => $data['area'] ?? ($order->area ?? ''),
                'alternative_number'    => $data['alternative_number'] ?? $order->alternative_number,
                'customer_confirmed_at' => now(),
            ]);

            return $user;
        });

        Auth::login($user, true);
        $request->session()->regenerate();

        Notify::admins(new OrderPlacedNotification($order->fresh(), 'admin'));

        return redirect()->route('customer.orders.show', $order->id)
            ->with('success', 'ধন্যবাদ! তথ্য সেভ হয়েছে ও আপনার অ্যাকাউন্ট তৈরি হয়েছে।');
    }

    /** Logged-in owner → their order; existing account → login; else null (show the form). */
    private function accountRedirect(Order $order)
    {
        $phone = Phone::normalize($order->mobile_number);
        $orderUrl = route('customer.orders.show', $order->id, false); // relative: login only honours local paths

        $current = Auth::user();
        if ($current && $current->role === 'customer' && $phone && $current->phone === $phone) {
            return redirect($orderUrl);
        }

        if ($order->customerAccount()) {
            return redirect()->route('customer.login', ['redirect' => $orderUrl])
                ->with('success', 'এই নম্বরে আপনার অ্যাকাউন্ট আছে — লগইন করে অর্ডার দেখুন।');
        }

        return null;
    }
}
