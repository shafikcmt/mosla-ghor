<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorCustomer;
use App\Notifications\OrderPlacedNotification;
use App\Notifications\PaymentUpdatedNotification;
use App\Services\StockService;
use App\Support\Notify;
use App\Support\VendorSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Vendor POS — lets an approved vendor create an order on a local customer's
 * behalf. Stock is deducted through {@see StockService} so every sale lands in
 * the movement ledger, and secure invoice/reorder tokens are minted up front
 * for the Phase-3 sharing flow.
 */
class PosOrderController extends Controller
{
    public function __construct(private StockService $stock)
    {
    }

    private function vendor()
    {
        return Auth::user()->vendor;
    }

    private function guard()
    {
        $vendor = $this->vendor();
        if (! $vendor?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }
        if (! VendorSettings::vendorCanCreateOrder()) {
            abort(403, 'অর্ডার তৈরি বন্ধ আছে।');
        }
        return $vendor;
    }

    /** Vendor-created (POS) orders only. */
    public function index(Request $request)
    {
        $vendor = $this->guard();

        $query = $vendor->createdOrders()->with('vendorCustomer')->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('order_number', 'like', "%{$s}%")
                  ->orWhere('customer_name', 'like', "%{$s}%")
                  ->orWhere('mobile_number', 'like', "%{$s}%");
            });
        }
        if ($request->payment === 'due') {
            $query->where('due_amount', '>', 0);
        } elseif ($request->payment === 'paid') {
            $query->where('due_amount', '<=', 0);
        }

        $orders = $query->paginate(20)->withQueryString();

        $base    = $vendor->createdOrders();
        $summary = [
            'count'     => (clone $base)->count(),
            'sales'     => (clone $base)->sum('grand_total'),
            'due_total' => (clone $base)->sum('due_amount'),
        ];

        return view('vendor.pos.index', compact('vendor', 'orders', 'summary'));
    }

    public function create()
    {
        $vendor = $this->guard();

        $products  = $vendor->products()
            ->where('is_active', true)
            ->orderBy('name_bn')
            ->get(['id', 'name_bn', 'name_en', 'sku', 'unit', 'selling_price', 'retail_price_1kg', 'stock', 'stock_qty']);
        $customers = $vendor->customers()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone', 'whatsapp']);

        return view('vendor.pos.create', compact('vendor', 'products', 'customers'));
    }

    public function store(Request $request)
    {
        $vendor = $this->guard();

        $data = $request->validate([
            'vendor_customer_id'  => ['nullable', 'integer', Rule::exists('vendor_customers', 'id')->where('vendor_id', $vendor->id)],
            'customer_name'       => 'required_without:vendor_customer_id|nullable|string|max:150',
            'customer_phone'      => 'nullable|string|max:30',
            'customer_address'    => 'nullable|string|max:500',
            'order_note'          => 'nullable|string|max:1000',
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer',
            'items.*.quantity'    => 'required|numeric|min:0.001',
            'items.*.unit_price'  => 'required|numeric|min:0',
            'items.*.discount'    => 'nullable|numeric|min:0',
            'order_discount'      => 'nullable|numeric|min:0',
            'paid_amount'         => 'nullable|numeric|min:0',
            'payment_method'      => 'nullable|string|max:30',
        ]);

        // Resolve the customer (existing local record or a walk-in snapshot).
        $customer = null;
        if (! empty($data['vendor_customer_id'])) {
            $customer = VendorCustomer::where('vendor_id', $vendor->id)->findOrFail($data['vendor_customer_id']);
        }
        $customerName  = $customer->name ?? $data['customer_name'];
        $customerPhone = $customer->phone ?? ($data['customer_phone'] ?? null);

        // Resolve + validate every line against this vendor's own products.
        $lines    = [];
        $subtotal = 0.0;
        foreach ($data['items'] as $row) {
            $product = $vendor->products()->find($row['product_id']);
            if (! $product) {
                return back()->withInput()->with('error', 'একটি পণ্য আপনার দোকানের নয় বা মুছে ফেলা হয়েছে।');
            }
            $qty       = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $lineDisc  = (float) ($row['discount'] ?? 0);
            $lineTotal = max(0, round($qty * $unitPrice - $lineDisc, 2));
            $subtotal += $lineTotal;

            $lines[] = [
                'product'    => $product,
                'attributes' => [
                    'vendor_id'       => $vendor->id,
                    'vendor_name'     => $vendor->shop_name,
                    'product_id'      => $product->id,
                    'product_name'    => $product->name_bn ?: $product->name_en,
                    'quantity'        => $qty,
                    'unit'            => $product->stockUnit(),
                    'unit_price'      => $unitPrice,
                    'discount_amount' => $lineDisc,
                    'line_total'      => $lineTotal,
                ],
            ];
        }

        // Order-level discount — gated + capped by admin policy.
        $orderDiscount = (float) ($data['order_discount'] ?? 0);
        if ($orderDiscount > 0) {
            if (! VendorSettings::vendorCanGiveDiscount()) {
                return back()->withInput()->with('error', 'ডিসকাউন্ট দেওয়ার অনুমতি নেই।');
            }
            $maxPct = VendorSettings::vendorMaxDiscountPercent();
            $maxAmt = round($subtotal * $maxPct / 100, 2);
            if ($orderDiscount > $maxAmt) {
                return back()->withInput()->with('error', "ডিসকাউন্ট সর্বোচ্চ {$maxPct}% (৳{$maxAmt}) পর্যন্ত দেওয়া যাবে।");
            }
        }
        $orderDiscount = min($orderDiscount, $subtotal);

        $grandTotal = round($subtotal - $orderDiscount, 2);
        $paid       = min((float) ($data['paid_amount'] ?? 0), $grandTotal);
        $due        = round($grandTotal - $paid, 2);

        if ($due > 0 && ! VendorSettings::vendorCanAllowDue()) {
            return back()->withInput()->with('error', 'বাকিতে বিক্রির অনুমতি নেই — সম্পূর্ণ টাকা গ্রহণ করুন।');
        }

        $paymentStatus = $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending');

        do {
            $orderNumber = 'POS-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        try {
            $order = DB::transaction(function () use (
                $vendor, $customer, $customerName, $customerPhone, $data,
                $lines, $subtotal, $orderDiscount, $grandTotal, $paid, $due,
                $paymentStatus, $orderNumber
            ) {
                $order = Order::create([
                    'order_number'         => $orderNumber,
                    'customer_name'        => $customerName,
                    'mobile_number'        => $customerPhone,
                    'full_address'         => $customer->address ?? ($data['customer_address'] ?? null),
                    'order_note'           => $data['order_note'] ?? null,
                    'order_type'           => 'retail',
                    'order_source'         => 'vendor_created_order',
                    'created_by_vendor_id' => $vendor->id,
                    'vendor_customer_id'   => $customer?->id,
                    'subtotal'             => $subtotal,
                    'discount_amount'      => $orderDiscount,
                    'packaging_cost'       => 0,
                    'delivery_charge'      => 0,
                    'grand_total'          => $grandTotal,
                    'payment_method'       => $data['payment_method'] ?? 'cash',
                    'paid_amount'          => $paid,
                    'partial_paid_amount'  => $paid,
                    'due_amount'           => $due,
                    'payment_status'       => $paymentStatus,
                    'order_status'         => 'processing',
                ]);

                foreach ($lines as $line) {
                    $order->items()->create($line['attributes']);
                }

                // Deduct on-hand through the ledger (throws if a line is short).
                $this->stock->deductForOrder($order, Auth::id());
                $order->update(['stock_deducted_at' => now()]);

                if ($customer && $due > 0) {
                    $customer->increment('due_balance', $due);
                }

                $order->ensureTokens();

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        // Admins get an in-app notice of the new vendor-created order.
        Notify::admins(new OrderPlacedNotification($order, 'admin'));

        return redirect()->route('vendor.pos.show', $order)->with('success', 'অর্ডার তৈরি হয়েছে — #' . $order->order_number);
    }

    /** Collect a (further) payment against a POS order's outstanding due. */
    public function collectPayment(Request $request, Order $order)
    {
        $vendor = $this->ownOrder($order);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($order->due_amount <= 0) {
            return back()->with('error', 'এই অর্ডারে কোনো বাকি নেই।');
        }

        $amount = min((float) $data['amount'], (float) $order->due_amount);
        $paid   = round((float) $order->paid_amount + $amount, 2);
        $due    = round((float) $order->grand_total - $paid, 2);

        DB::transaction(function () use ($order, $paid, $due, $amount) {
            $order->update([
                'paid_amount'         => $paid,
                'partial_paid_amount' => $paid,
                'due_amount'          => max(0, $due),
                'payment_status'      => $due <= 0 ? 'paid' : 'partial',
            ]);
            if ($order->vendorCustomer) {
                $newBal = max(0, (float) $order->vendorCustomer->due_balance - $amount);
                $order->vendorCustomer->update(['due_balance' => $newBal]);
            }
        });

        Notify::admins(new PaymentUpdatedNotification($order->fresh(), $due <= 0 ? 'paid' : 'partial'));

        return back()->with('success', '৳' . number_format($amount, 0) . ' পেমেন্ট গ্রহণ করা হয়েছে।');
    }

    public function show(Order $order)
    {
        $vendor = $this->ownOrder($order);

        $order->load(['items', 'vendorCustomer']);
        $order->ensureTokens();

        return view('vendor.pos.show', compact('vendor', 'order'));
    }

    /** Build the filled WhatsApp invoice message and redirect to wa.me. */
    public function whatsapp(Order $order)
    {
        $vendor = $this->ownOrder($order);

        if (! VendorSettings::vendorCanShareWhatsapp()) {
            return back()->with('error', 'WhatsApp শেয়ারের অনুমতি নেই।');
        }

        $order->ensureTokens();

        $number = $order->vendorCustomer?->whatsappNumber() ?: $order->mobile_number;
        $number = preg_replace('/\D/', '', (string) $number);
        if ($number === '') {
            return back()->with('error', 'কাস্টমারের ফোন/WhatsApp নম্বর নেই।');
        }
        // Local 01XXXXXXXXX → 8801XXXXXXXXX for wa.me.
        if (Str::startsWith($number, '0')) {
            $number = '88' . $number;
        }

        $message = $this->renderWhatsappMessage($order, $vendor);

        $order->update(['whatsapp_sent_at' => now()]);

        return redirect()->away('https://wa.me/' . $number . '?text=' . rawurlencode($message));
    }

    /** Enable/disable the public invoice link. */
    public function invoiceToggle(Order $order)
    {
        $this->ownOrder($order);

        $order->update(['invoice_disabled_at' => $order->invoice_disabled_at ? null : now()]);

        return back()->with('success', $order->invoice_disabled_at ? 'ইনভয়েস লিংক বন্ধ করা হয়েছে।' : 'ইনভয়েস লিংক চালু করা হয়েছে।');
    }

    private function ownOrder(Order $order)
    {
        $vendor = $this->guard();
        if ($order->created_by_vendor_id !== $vendor->id) {
            abort(403, 'এই অর্ডারে আপনার অ্যাক্সেস নেই।');
        }
        return $vendor;
    }

    private function renderWhatsappMessage(Order $order, $vendor): string
    {
        $template = VendorSettings::whatsappInvoiceTemplate();

        return strtr($template, [
            '{customer_name}' => $order->customer_name ?: ($order->vendorCustomer?->name ?? 'কাস্টমার'),
            '{order_number}'  => $order->order_number,
            '{total}'         => number_format((float) $order->grand_total, 0),
            '{due}'           => number_format((float) $order->due_amount, 0),
            '{invoice_link}'  => $order->invoiceUrl() ?? '',
            '{reorder_link}'  => $order->reorderUrl() ?? '',
            '{payment_link}'  => $order->paymentUrl() ?? '',
            '{shop_name}'     => $vendor->shop_name ?? '',
        ]);
    }
}
