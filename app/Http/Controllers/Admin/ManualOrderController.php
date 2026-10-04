<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliverySetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Phone;
use App\Models\WebsiteSetting;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Orders that arrive by phone call / WhatsApp / Messenger. The admin enters
 * them here, an invoice link is minted straight away, and the filled invoice
 * message can be sent to the customer's WhatsApp in one click.
 */
class ManualOrderController extends Controller
{
    /** Default WhatsApp invoice message for admin-sent invoices. */
    public const DEFAULT_TEMPLATE = "🧾 *{shop_name} — ইনভয়েস*\n━━━━━━━━━━━━━━━\nআসসালামু আলাইকুম {customer_name},\nআপনার অর্ডার কনফার্ম হয়েছে ✅\n\n📦 অর্ডার: *#{order_number}*\n{items}\n━━━━━━━━━━━━━━━\nসাবটোটাল: ৳{subtotal}\nডেলিভারি চার্জ: ৳{delivery_charge}\n{discount_line}💰 *মোট: ৳{total}*\n✅ পরিশোধিত: ৳{paid}\n🔴 *বাকি (ডেলিভারিতে দিবেন): ৳{due}*\n\n📍 ঠিকানা: {address}\n━━━━━━━━━━━━━━━\n{account_block}🧾 ইনভয়েস দেখুন / পেমেন্ট করুন:\n{invoice_link}\n\n📄 PDF ইনভয়েস:\n{invoice_pdf_link}\n━━━━━━━━━━━━━━━\nযেকোনো প্রয়োজনে: {shop_phone}\nধন্যবাদ 🙏";

    public function __construct(private StockService $stock)
    {
    }

    public function create()
    {
        $products = Product::query()
            ->where(function ($q) {
                $q->whereNull('vendor_id')->orWhere('approval_status', 'approved');
            })
            ->with('activeRetailPrices')
            ->orderByDesc('is_active')
            ->orderBy('name_bn')
            ->get();

        $catalog = $products->map(fn (Product $p) => [
            'id'     => $p->id,
            'name'   => $p->name_bn ?: $p->name_en,
            'sku'    => $p->sku,
            'unit'   => $p->stockUnit(),
            'price'  => (float) ($p->selling_price ?: $p->retail_price_1kg ?: 0),
            'onhand' => round($p->onHand(), 3),
            'active' => (bool) $p->is_active,
            'packs'  => $p->activeRetailPrices
                ->whereNull('product_variant_id')
                ->map(fn ($pr) => [
                    'id'    => $pr->id,
                    'label' => $pr->label ?: ($pr->quantity_gram >= 1000 ? ($pr->quantity_gram / 1000) . ' কেজি' : $pr->quantity_gram . ' গ্রাম'),
                    'grams' => (int) $pr->quantity_gram,
                    'price' => (float) $pr->final_price,
                ])->values(),
        ])->values();

        $delivery = DeliverySetting::current();

        return view('admin.orders.manual-create', [
            'catalog'  => $catalog,
            'channels' => Order::CHANNELS,
            'inside'   => (float) $delivery->inside_dhaka_charge,
            'outside'  => (float) $delivery->outside_dhaka_charge,
        ]);
    }

    /** Autofill name/address from this phone number's last order. */
    public function lookup(Request $request)
    {
        $phone = preg_replace('/\D/', '', (string) $request->query('phone', ''));
        if (strlen($phone) < 10) {
            return response()->json(['found' => false, 'registered' => false]);
        }
        $account = User::where('role', 'customer')->where('phone', Phone::normalize($phone))->first();
        $last = Order::where('mobile_number', 'like', '%' . substr($phone, -10))
            ->latest()
            ->first(['customer_name', 'full_address', 'district', 'area', 'alternative_number']);

        $count = $last ? Order::where('mobile_number', 'like', '%' . substr($phone, -10))->count() : 0;

        return response()->json([
            'registered' => (bool) $account,
            'account_name' => $account?->name,
            'found'   => (bool) $last,
            'orders'  => $count,
            'name'    => $last?->customer_name ?: $account?->name,
            'address' => $last?->full_address,
            'district'=> $last?->district,
            'area'    => $last?->area,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'order_channel'      => ['nullable', Rule::in(array_keys(Order::CHANNELS))],
            'customer_name'      => 'nullable|string|max:100',
            'mobile_number'      => ['required', 'string', 'max:20', function ($attr, $value, $fail) {
                if (! Phone::isValidBd($value)) {
                    $fail('সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।');
                }
            }],
            'alternative_number' => 'nullable|string|max:20',
            'full_address'       => 'nullable|string|max:1000',
            'district'           => 'nullable|string|max:80',
            'area'               => 'nullable|string|max:80',
            'order_note'         => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.price_id'   => 'nullable|integer',
            'items.*.quantity'   => 'required|numeric|min:0.001',
            'items.*.unit_price' => 'required|numeric|min:0',
            'delivery_charge'    => 'nullable|numeric|min:0',
            'discount_amount'    => 'nullable|numeric|min:0',
            'paid_amount'        => 'nullable|numeric|min:0',
            'payment_method'     => 'nullable|string|max:30',
            'transaction_id'     => 'nullable|string|max:100',
            'order_status'       => ['nullable', Rule::in(['pending', 'confirmed', 'processing'])],
            'deduct_stock'       => 'nullable|boolean',
        ], [
            'mobile_number.required' => 'মোবাইল নম্বর দিন।',
            'items.required'         => 'অন্তত একটি পণ্য যোগ করুন।',
        ]);

        // Resolve every line against the catalog (packs must belong to the product).
        $lines    = [];
        $subtotal = 0.0;
        $weight   = 0;
        foreach ($data['items'] as $row) {
            $product = Product::with('activeRetailPrices')->find($row['product_id']);
            $pack    = null;
            if (! empty($row['price_id'])) {
                $pack = $product->activeRetailPrices->firstWhere('id', (int) $row['price_id']);
                if (! $pack) {
                    return back()->withInput()->with('error', 'একটি প্যাক সাইজ পণ্যের সাথে মেলেনি — পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
                }
            }

            $qty       = (float) $row['quantity'];
            $unitPrice = (float) $row['unit_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $subtotal += $lineTotal;
            $name      = $product->name_bn ?: $product->name_en;

            if ($pack) {
                $packLabel = $pack->label ?: ($pack->quantity_gram . ' গ্রাম');
                $grams     = (int) round($pack->quantity_gram * $qty);
                $attrs = [
                    'product_name'  => $name . ' — ' . $packLabel,
                    'sell_type'     => 'retail',
                    'price_id'      => $pack->id,
                    'quantity'      => $qty,
                    'unit'          => 'টি',
                    'quantity_gram' => $grams,
                ];
            } else {
                $unit  = $product->stockUnit();
                $grams = match ($unit) {
                    'kg'    => (int) round($qty * 1000),
                    'gram'  => (int) round($qty),
                    default => 0,
                };
                $attrs = [
                    'product_name'  => $name,
                    'sell_type'     => 'retail',
                    'quantity'      => $qty,
                    'unit'          => $unit,
                    'quantity_gram' => $grams ?: null,
                ];
            }
            $weight += $grams;

            $lines[] = $attrs + [
                'product_id'      => $product->id,
                'vendor_id'       => $product->vendor_id,
                'unit_price'      => $unitPrice,
                'discount_amount' => 0,
                'line_total'      => $lineTotal,
            ];
        }

        $delivery   = round((float) ($data['delivery_charge'] ?? 0), 2);
        $discount   = min(round((float) ($data['discount_amount'] ?? 0), 2), $subtotal + $delivery);
        $grandTotal = round($subtotal + $delivery - $discount, 2);
        $paid       = min(round((float) ($data['paid_amount'] ?? 0), 2), $grandTotal);
        $due        = round($grandTotal - $paid, 2);

        do {
            $orderNumber = 'MSL-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        $deductStock = $request->boolean('deduct_stock', true);

        try {
            $order = DB::transaction(function () use ($data, $lines, $subtotal, $delivery, $discount, $grandTotal, $paid, $due, $weight, $orderNumber, $deductStock) {
                $order = Order::create([
                    'order_number'       => $orderNumber,
                    'customer_name'      => ($data['customer_name'] ?? null) ?: 'কাস্টমার',
                    'mobile_number'      => Phone::normalize($data['mobile_number']),
                    'alternative_number' => $data['alternative_number'] ?? null,
                    'full_address'       => $data['full_address'] ?? '',
                    'district'           => $data['district'] ?? '',
                    'area'               => $data['area'] ?? '',
                    'order_note'         => $data['order_note'] ?? null,
                    'order_type'         => 'retail',
                    'order_source'       => 'admin_manual_order',
                    'order_channel'      => $data['order_channel'] ?? 'phone',
                    'subtotal'           => $subtotal,
                    'packaging_cost'     => 0,
                    'delivery_charge'    => $delivery,
                    'delivery_charge_overridden' => true,
                    'discount_amount'    => $discount,
                    'grand_total'        => $grandTotal,
                    'weight_gram'        => $weight ?: null,
                    'payment_method'     => ($data['payment_method'] ?? null) ?: 'cash_on_delivery',
                    'transaction_id'     => $data['transaction_id'] ?? null,
                    'paid_amount'        => $paid,
                    'partial_paid_amount'=> $paid,
                    'due_amount'         => $due,
                    'payment_status'     => $due <= 0 ? 'verified' : 'pending',
                    'order_status'       => ($data['order_status'] ?? null) ?: 'confirmed',
                ]);

                foreach ($lines as $line) {
                    $order->items()->create($line);
                }

                if ($deductStock) {
                    $this->stock->deductForAdminOrder($order, Auth::id());
                    $order->update(['stock_deducted_at' => now()]);
                }

                $order->ensureTokens();

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage() . ' (স্টক না কেটে অর্ডার নিতে "স্টক কাটুন" অপশন বন্ধ করুন।)');
        }

        return redirect()->route('admin.orders.send', $order);
    }

    /** "Order saved" screen with one big WhatsApp button. */
    public function send(Order $order)
    {
        $order->ensureTokens();
        $order->load('items');

        return view('admin.orders.manual-send', [
            'order'      => $order,
            'message'    => self::renderMessage($order),
            'registered' => (bool) $order->customerAccount(),
        ]);
    }

    /** Mint the public invoice link for an order that doesn't have one yet. */
    public function prepareInvoice(Order $order)
    {
        $order->ensureTokens();

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'ইনভয়েস লিংক তৈরি হয়েছে।')
            ->with('open_whatsapp', true);
    }

    /** Send the (possibly edited) invoice message to the customer's WhatsApp. */
    public function whatsapp(Request $request, Order $order)
    {
        $data = $request->validate([
            'phone'   => 'required|string|max:30',
            'message' => 'required|string|max:5000',
        ], [
            'phone.required'   => 'WhatsApp নম্বর দিন।',
            'message.required' => 'মেসেজ খালি রাখা যাবে না।',
        ]);

        $number = self::waNumber($data['phone']);
        if ($number === '') {
            return back()->with('error', 'সঠিক WhatsApp নম্বর দিন।');
        }

        $order->update(['whatsapp_sent_at' => now()]);

        return redirect()->away('https://wa.me/' . $number . '?text=' . rawurlencode($data['message']));
    }

    /** Enable / disable the public invoice link. */
    public function invoiceToggle(Order $order)
    {
        $order->update(['invoice_disabled_at' => $order->invoice_disabled_at ? null : now()]);

        return back()->with('success', $order->invoice_disabled_at ? 'ইনভয়েস লিংক বন্ধ করা হয়েছে।' : 'ইনভয়েস লিংক চালু করা হয়েছে।');
    }

    /** 01XXXXXXXXX / +8801… / 8801… → 8801XXXXXXXXX for wa.me. */
    public static function waNumber(?string $phone): string
    {
        $number = preg_replace('/\D/', '', (string) $phone);
        if (Str::startsWith($number, '0')) {
            $number = '88' . $number;
        }
        return strlen($number) >= 10 ? $number : '';
    }

    public static function template(): string
    {
        $tpl = WebsiteSetting::get('admin_whatsapp_invoice_template', '');
        return trim((string) $tpl) !== '' ? $tpl : self::DEFAULT_TEMPLATE;
    }

    /** Fill the WhatsApp invoice template for an order. */
    public static function renderMessage(Order $order): string
    {
        $order->loadMissing('items');
        $money = fn ($v) => number_format((float) $v, 0);

        $items = $order->items->map(fn ($it) => '• ' . $it->product_name . ' — ' . trim($it->quantityLabel()) . ' = ৳' . $money($it->line_total))
            ->implode("\n");

        $discount = (float) $order->discount_amount + (float) $order->payment_discount;

        // Registered → login link; otherwise a link to add address + create an account.
        if ($order->customerAccount()) {
            $accountBlock = "👤 আপনার অ্যাকাউন্টে অর্ডার ট্র্যাক করুন:\n" . route('customer.login', ['redirect' => route('customer.orders.show', $order->id, false)]) . "\n\n";
        } elseif ($order->accountUrl()) {
            $accountBlock = (trim((string) $order->full_address) === ''
                    ? "⚠️ *ডেলিভারির জন্য আপনার ঠিকানা দিন* (অ্যাকাউন্টও খুলে যাবে):\n"
                    : "📝 আপনার তথ্য দিয়ে অ্যাকাউন্ট খুলুন — অর্ডার ট্র্যাক ও পরে সহজে অর্ডার করতে:\n")
                . $order->accountUrl() . "\n\n";
        } else {
            $accountBlock = '';
        }

        return strtr(self::template(), [
            '{shop_name}'        => WebsiteSetting::get('site_name', 'মসলা ঘর'),
            '{shop_phone}'       => WebsiteSetting::get('phone', ''),
            '{customer_name}'    => $order->customer_name ?: 'কাস্টমার',
            '{order_number}'     => $order->order_number,
            '{items}'            => $items,
            '{subtotal}'         => $money($order->subtotal),
            '{delivery_charge}'  => $money($order->delivery_charge),
            '{discount_line}'    => $discount > 0 ? 'ছাড়: −৳' . $money($discount) . "\n" : '',
            '{total}'            => $money($order->grand_total),
            '{paid}'             => $money($order->effectivePaid()),
            '{due}'              => $money($order->effectiveDue()),
            '{address}'          => $order->full_address ?: '(লিংকে দিন)',
            '{account_block}'    => $accountBlock,
            '{invoice_link}'     => $order->invoiceUrl() ?? '',
            '{invoice_pdf_link}' => $order->invoicePdfUrl() ?? '',
            '{payment_link}'     => $order->paymentUrl() ?? '',
        ]);
    }
}
