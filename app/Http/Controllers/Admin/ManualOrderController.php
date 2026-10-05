<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderVoucherNotification;
use App\Services\StockService;
use App\Support\GuestWholesaleAccount;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

/**
 * Admin "ফোন / WhatsApp অর্ডার" — POS-style order entry for buyers who order
 * by call or chat. Links (or creates) the customer account by phone, deducts
 * stock through the ledger, then opens a voucher page to share the invoice
 * (WhatsApp / email / PDF) together with a set-password registration link.
 */
class ManualOrderController extends Controller
{
    public const CHANNELS = ['phone' => 'ফোন কল', 'whatsapp' => 'WhatsApp', 'facebook' => 'Facebook', 'walk_in' => 'সরাসরি / দোকানে'];
    public const PAYMENTS = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'rocket' => 'Rocket', 'bank' => 'ব্যাংক ট্রান্সফার', 'cash' => 'নগদ (হাতে)'];

    public function __construct(private StockService $stock)
    {
    }

    public function create(Request $request)
    {
        $products = Product::where('is_active', true)->orderBy('name_bn')
            ->with(['activePrices' => fn ($q) => $q->whereNull('product_variant_id')])
            ->get(['id', 'name_bn', 'name_en', 'unit', 'retail_price_1kg', 'selling_price', 'stock', 'stock_qty']);
        $customers = Customer::orderByDesc('last_order_at')->limit(500)->get(['name', 'mobile_number', 'email', 'last_full_address']);

        // Pre-fill from a wholesale enquiry ("অর্ডার তৈরি করুন" on the enquiry page).
        $prefill = [];
        if ($request->filled('enquiry')) {
            $e = \App\Models\WholesaleEnquiry::find($request->integer('enquiry'));
            if ($e) {
                $prefill = ['enquiry_id' => $e->id, 'name' => $e->customer_name, 'phone' => $e->customer_phone, 'email' => $e->customer_email,
                    'address' => $e->delivery_location, 'product_id' => $e->product_id, 'qty' => (float) $e->quantity_kg,
                    'type' => 'wholesale', 'channel' => $e->contact_channel === 'call' ? 'phone' : ($e->contact_channel ?: 'phone')];
            }
        }

        return view('admin.orders.manual-create', [
            'products'  => $products,
            'customers' => $customers,
            'prefill'   => $prefill,
            'channels'  => self::CHANNELS,
            'payments'  => self::PAYMENTS,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge(['customer_phone' => Phone::normalize($request->input('customer_phone')) ?? $request->input('customer_phone')]);

        $data = $request->validate([
            'customer_name'      => ['required', 'string', 'max:150'],
            'customer_phone'     => ['required', 'regex:/^01[3-9]\d{8}$/'],
            'customer_email'     => ['nullable', 'email', 'max:150'],
            'full_address'       => ['nullable', 'string', 'max:500'],
            'district'           => ['nullable', 'string', 'max:80'],
            'channel'            => ['required', 'in:'.implode(',', array_keys(self::CHANNELS))],
            'order_type'         => ['required', 'in:retail,wholesale'],
            'items'              => ['required', 'array', 'min:1', 'max:30'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            // Optional pack (২৫ গ্রাম, ১ কেজি…): quantity = number of packs, price = pack price.
            'items.*.price_id'   => ['nullable', 'integer', 'exists:product_prices,id'],
            'items.*.quantity'   => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'delivery_charge'    => ['nullable', 'numeric', 'min:0'],
            'discount'           => ['nullable', 'numeric', 'min:0'],
            'paid_amount'        => ['nullable', 'numeric', 'min:0'],
            'payment_method'     => ['required', 'in:'.implode(',', array_keys(self::PAYMENTS))],
            'order_note'         => ['nullable', 'string', 'max:1000'],
            'enquiry_id'         => ['nullable', 'integer', 'exists:wholesale_enquiries,id'],
        ], [
            'customer_phone.regex' => 'সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।',
            'items.required'       => 'অন্তত একটি পণ্য যোগ করুন।',
        ]);

        $lines    = [];
        $subtotal = 0.0;
        foreach ($data['items'] as $row) {
            $product   = Product::findOrFail($row['product_id']);
            $qty       = round((float) $row['quantity'], 3);
            $unitPrice = (float) $row['unit_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $unit      = $product->stockUnit();
            $grams     = in_array($unit, ['kg', 'কেজি'], true) ? (int) round($qty * 1000) : null;
            $name      = $product->name_bn ?: $product->name_en;

            $pack = ! empty($row['price_id'])
                ? $product->activePrices()->whereKey($row['price_id'])->first()
                : null;
            if (! empty($row['price_id']) && ! $pack) {
                return back()->withInput()->with('error', "\"{$name}\"-এর বেছে নেওয়া প্যাকটি আর চালু নেই।");
            }
            if ($pack) {
                // e.g. 3 × "২৫ গ্রাম" → unit "২৫ গ্রাম প্যাক", 75 g for stock.
                $unit  = mb_substr(($pack->label ?: $pack->quantity_gram.'g').' প্যাক', 0, 20);
                $grams = (int) $pack->quantity_gram * (int) round($qty);
                $qty   = (float) (int) round($qty);
                if ($qty < 1) {
                    return back()->withInput()->with('error', "\"{$name}\" — প্যাকের সংখ্যা অন্তত ১ দিন।");
                }
                $lineTotal = round($qty * $unitPrice, 2);
            }
            $subtotal += $lineTotal;
            $lines[] = [
                'vendor_id'     => $product->vendor_id,
                'vendor_name'   => $product->vendor?->shop_name,
                'product_id'    => $product->id,
                'product_name'  => $name,
                'price_id'      => $pack?->id,
                'sell_type'     => $pack->sell_type ?? $data['order_type'],
                'quantity'      => $qty,
                'unit'          => $unit,
                'quantity_gram' => $grams,
                'unit_price'    => $unitPrice,
                'line_total'    => $lineTotal,
            ];
        }

        $delivery = (float) ($data['delivery_charge'] ?? 0);
        $discount = min((float) ($data['discount'] ?? 0), $subtotal);
        $grand    = round($subtotal + $delivery - $discount, 2);
        $paid     = min((float) ($data['paid_amount'] ?? 0), $grand);
        $due      = round($grand - $paid, 2);
        $location = trim(($data['district'] ?? '') ?: '—');

        // Link to (or create) the customer's account by phone — same as guest enquiries.
        $account = GuestWholesaleAccount::findOrCreate($data['customer_name'], $data['customer_phone'], $data['customer_email'] ?? null);

        do {
            $number = 'MO-'.date('ymd').'-'.strtoupper(Str::random(4));
        } while (Order::withTrashed()->where('order_number', $number)->exists());

        try {
            $order = DB::transaction(function () use ($data, $lines, $subtotal, $delivery, $discount, $grand, $paid, $due, $location, $account, $number) {
                $order = Order::create([
                    'order_number'    => $number,
                    'customer_name'   => $data['customer_name'],
                    'mobile_number'   => $data['customer_phone'],
                    'full_address'    => ($data['full_address'] ?? '') ?: $location,
                    'district'        => Str::limit($location, 78, ''),
                    'area'            => Str::limit($location, 78, ''),
                    'order_type'      => $data['order_type'],
                    'order_source'    => 'admin_manual_order',
                    'customer_id'     => $account['customer']->id,
                    'enquiry_id'      => $data['enquiry_id'] ?? null,
                    'subtotal'        => $subtotal,
                    'discount_amount' => $discount,
                    'packaging_cost'  => 0,
                    'delivery_charge' => $delivery,
                    'grand_total'     => $grand,
                    'payment_method'  => $data['payment_method'],
                    'paid_amount'     => $paid,
                    'partial_paid_amount' => $paid,
                    'due_amount'      => $due,
                    'payment_status'  => $due <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
                    'order_status'    => 'processing',
                    'order_note'      => trim(self::CHANNELS[$data['channel']].'-এ নেওয়া অর্ডার। '.($data['order_note'] ?? '')),
                ]);
                foreach ($lines as $line) {
                    $order->items()->create($line);
                }

                $this->stock->deductForOrder($order, Auth::id());
                $order->update(['stock_deducted_at' => now()]);

                Customer::where('id', $account['customer']->id)->update([
                    'last_full_address' => $order->full_address,
                    'last_order_at'     => now(),
                    'total_orders'      => DB::raw('total_orders + 1'),
                    'total_spent'       => DB::raw('total_spent + '.$grand),
                ]);

                if (! empty($data['enquiry_id'])) {
                    \App\Models\WholesaleEnquiry::whereKey($data['enquiry_id'])->update(['status' => 'accepted']);
                }

                $order->ensureTokens();

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.orders.manual.share', $order)
            ->with('success', 'অর্ডার #'.$order->order_number.' তৈরি হয়েছে — এখন ভাউচার শেয়ার করুন।');
    }

    /** Voucher + share page (WhatsApp / email / PDF / link). */
    public function share(Order $order)
    {
        $order->load('items');
        $order->ensureTokens();
        $setPasswordUrl = $this->setPasswordUrl($order);

        return view('admin.orders.manual-share', [
            'order'          => $order,
            'setPasswordUrl' => $setPasswordUrl,
            'whatsappUrl'    => $this->whatsappUrl($order, $setPasswordUrl),
            'email'          => $order->customer?->email,
        ]);
    }

    public function email(Request $request, Order $order)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);
        $order->load('items');
        $order->ensureTokens();

        try {
            Notification::route('mail', $data['email'])->notifyNow(new OrderVoucherNotification($order, $this->setPasswordUrl($order)));
        } catch (\Throwable $e) {
            return back()->with('error', 'ইমেইল পাঠানো যায়নি: '.$e->getMessage());
        }

        if ($order->customer && empty($order->customer->email)) {
            $order->customer->update(['email' => mb_strtolower($data['email'])]);
        }

        return back()->with('success', $data['email'].' ঠিকানায় ভাউচার পাঠানো হয়েছে।');
    }

    /** A signed set-password link while the customer's login has no password of their own yet. */
    private function setPasswordUrl(Order $order): ?string
    {
        $user = User::where('phone', $order->mobile_number)->where('role', 'customer')->first();
        if (! $user || $user->password_set_at) {
            return null;
        }

        return GuestWholesaleAccount::setPasswordUrlFor($user);
    }

    private function whatsappUrl(Order $order, ?string $setPasswordUrl): ?string
    {
        $wa = Phone::toWa($order->mobile_number);
        if (! $wa) {
            return null;
        }

        $items = $order->items->map(fn ($i) => '• '.$i->product_name.' — '
            .rtrim(rtrim(number_format((float) $i->quantity, 3, '.', ''), '0'), '.').' '.$i->unit
            .' × ৳'.number_format((float) $i->unit_price, 0).' = ৳'.number_format((float) $i->line_total, 0))->implode("\n");

        $text = "🧾 *".\App\Models\WebsiteSetting::siteName()." — অর্ডার ভাউচার*\n"
            ."অর্ডার নং: #{$order->order_number}\n"
            ."আসসালামু আলাইকুম {$order->customer_name},\n\n"
            .$items."\n\n"
            .((float) $order->delivery_charge > 0 ? '🚚 ডেলিভারি: ৳'.number_format((float) $order->delivery_charge, 0)."\n" : '')
            .((float) $order->discount_amount > 0 ? '🎁 ছাড়: ৳'.number_format((float) $order->discount_amount, 0)."\n" : '')
            .'💰 *মোট: ৳'.number_format((float) $order->grand_total, 0)."*\n"
            .((float) $order->paid_amount > 0 ? '✅ পরিশোধিত: ৳'.number_format((float) $order->paid_amount, 0)."\n" : '')
            .((float) $order->due_amount > 0 ? '⏳ বাকি: ৳'.number_format((float) $order->due_amount, 0)."\n" : '')
            ."\n📄 ভাউচার (PDF): ".$order->invoicePdfUrl()."\n"
            .($setPasswordUrl ? "\n🔐 অর্ডার ট্র্যাক ও পরের বার সহজে অর্ডার করতে অ্যাকাউন্ট চালু করুন:\n{$setPasswordUrl}\n" : '')
            ."\nধন্যবাদ 🌿";

        return 'https://wa.me/'.$wa.'?text='.rawurlencode($text);
    }
}
