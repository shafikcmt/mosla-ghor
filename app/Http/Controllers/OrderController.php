<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\PriceSetting;
use App\Models\ProductPrice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $paymentSettings = PaymentSetting::current();
        $enabledMethods  = $paymentSettings->enabledMethods();

        if (empty($enabledMethods)) {
            $enabledMethods = ['cash_on_delivery'];
        }

        $isManualPayment = in_array($request->input('payment_method'), ['bkash', 'rocket', 'nagad']);

        $validated = $request->validate([
            'full_name'                => ['required', 'string', 'max:100'],
            'mobile_number'            => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'alternative_number'       => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'full_address'             => ['required', 'string', 'max:500'],
            'district'                 => ['required', 'string', 'max:80'],
            'area'                     => ['required', 'string', 'max:80'],
            'order_note'               => ['nullable', 'string', 'max:500'],
            'payment_method'           => ['required', 'string', Rule::in($enabledMethods)],
            'sender_number'            => [$isManualPayment ? 'required' : 'nullable', 'string', 'max:30'],
            'transaction_id'           => [$isManualPayment ? 'required' : 'nullable', 'string', 'max:100'],
            'paid_amount'              => [$isManualPayment ? 'required' : 'nullable', 'numeric', 'min:0'],
            'items'                    => ['required', 'array', 'min:1', 'max:20'],
            'items.*.product_id'       => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity_gram'    => ['required', 'integer', 'in:25,50,100,250,500,1000'],
        ], [
            'mobile_number.regex'      => 'সঠিক মোবাইল নম্বর দিন। যেমন: 01700000000',
            'alternative_number.regex' => 'সঠিক বিকল্প নম্বর দিন। যেমন: 01700000000',
            'payment_method.in'        => 'একটি বৈধ পেমেন্ট পদ্ধতি বেছে নিন।',
            'sender_number.required'   => 'সেন্ডার নম্বর দিন।',
            'transaction_id.required'  => 'ট্রানজেকশন আইডি দিন।',
            'paid_amount.required'     => 'পেমেন্ট করা পরিমাণ দিন।',
            'items.required'           => 'কমপক্ষে একটি পণ্য যোগ করুন।',
            'items.min'                => 'কমপক্ষে একটি পণ্য যোগ করুন।',
            'items.max'                => 'সর্বোচ্চ ২০টি পণ্য যোগ করা যাবে।',
        ]);

        // ── Backend price recalculation (never trust frontend prices) ──────────
        $settings       = PriceSetting::current();
        $subtotal       = 0.0;
        $processedItems = [];

        foreach ($validated['items'] as $item) {
            $productPrice = ProductPrice::with('product')
                ->where('product_id', (int) $item['product_id'])
                ->where('quantity_gram', (int) $item['quantity_gram'])
                ->where('is_active', true)
                ->first();

            if (! $productPrice || ! $productPrice->product?->is_active) {
                return response()->json([
                    'message' => 'একটি পণ্যের মূল্য পাওয়া যায়নি। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।',
                    'errors'  => ['items' => ['একটি পণ্যের তথ্য পাওয়া যায়নি।']],
                ], 422);
            }

            $lineTotal = (float) $productPrice->final_price;
            $subtotal += $lineTotal;

            $processedItems[] = [
                'product_id'    => $productPrice->product->id,
                'product_name'  => $productPrice->product->name_bn,
                'quantity_gram' => (int) $item['quantity_gram'],
                'unit_price'    => $lineTotal,
                'line_total'    => $lineTotal,
            ];
        }

        $packagingCost = (float) $settings->default_packaging_cost;
        $grandTotal    = $subtotal + $packagingCost;

        // ── Generate unique order number ────────────────────────────────────────
        do {
            $orderNumber = 'MSL-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        // ── Persist order + items in a transaction ──────────────────────────────
        $order = DB::transaction(function () use ($validated, $processedItems, $subtotal, $packagingCost, $grandTotal, $orderNumber, $isManualPayment) {
            $order = Order::create([
                'order_number'       => $orderNumber,
                'customer_name'      => $validated['full_name'],
                'mobile_number'      => $validated['mobile_number'],
                'alternative_number' => $validated['alternative_number'] ?? null,
                'full_address'       => $validated['full_address'],
                'district'           => $validated['district'],
                'area'               => $validated['area'],
                'order_note'         => $validated['order_note'] ?? null,
                'order_type'         => 'custom',
                'subtotal'           => $subtotal,
                'packaging_cost'     => $packagingCost,
                'delivery_charge'    => 0.00,
                'grand_total'        => $grandTotal,
                'payment_method'     => $validated['payment_method'],
                'sender_number'      => $isManualPayment ? ($validated['sender_number'] ?? null) : null,
                'transaction_id'     => $isManualPayment ? ($validated['transaction_id'] ?? null) : null,
                'paid_amount'        => $isManualPayment ? ($validated['paid_amount'] ?? null) : null,
                'payment_status'     => 'pending',
                'order_status'       => 'pending',
            ]);

            foreach ($processedItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });

        return response()->json([
            'success'  => true,
            'redirect' => route('order.success', $order->order_number),
        ]);
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        return view('order-success', compact('order'));
    }
}
