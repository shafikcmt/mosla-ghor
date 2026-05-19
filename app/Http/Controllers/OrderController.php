<?php

namespace App\Http\Controllers;

use App\Models\BdDistrict;
use App\Models\BdDivision;
use App\Models\BdUnion;
use App\Models\BdUpazila;
use App\Models\Combo;
use App\Models\Customer;
use App\Models\DeliveryLocation;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\PaymentSetting;
use App\Models\PriceSetting;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\WebsiteSetting;
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

        $isComboOrder = $request->filled('combo_id');

        $validated = $request->validate([
            'full_name'                => ['required', 'string', 'max:100'],
            'mobile_number'            => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'alternative_number'       => ['nullable', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'full_address'             => ['required', 'string', 'max:500'],
            'order_note'               => ['nullable', 'string', 'max:500'],
            'bd_division_id'           => ['required', 'integer', 'exists:bd_divisions,id'],
            'bd_district_id'           => ['required', 'integer', 'exists:bd_districts,id'],
            'bd_upazila_id'            => ['required', 'integer', 'exists:bd_upazilas,id'],
            'bd_union_id'              => ['nullable', 'integer', 'exists:bd_unions,id'],
            'combo_id'                 => ['nullable', 'integer', 'exists:combos,id'],
            'delivery_zone_id'         => ['required', 'integer', 'exists:delivery_zones,id'],
            'delivery_location_id'     => ['required', 'integer', 'exists:delivery_locations,id'],
            'payment_method'           => ['required', 'string', Rule::in($enabledMethods)],
            'sender_number'            => [$isManualPayment ? 'required' : 'nullable', 'string', 'max:30'],
            'transaction_id'           => [$isManualPayment ? 'required' : 'nullable', 'string', 'max:100'],
            'paid_amount'              => [$isManualPayment ? 'required' : 'nullable', 'numeric', 'min:0'],
            'payment_screenshot'       => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'items'                    => [$isComboOrder ? 'nullable' : 'required', 'array', 'min:1', 'max:20'],
            'items.*.product_id'       => ['required_with:items', 'integer', 'exists:products,id'],
            'items.*.quantity_gram'    => ['required_with:items', 'integer', 'in:25,50,100,250,500,1000'],
        ], [
            'mobile_number.regex'          => 'সঠিক মোবাইল নম্বর দিন। যেমন: 01700000000',
            'alternative_number.regex'     => 'সঠিক বিকল্প নম্বর দিন। যেমন: 01700000000',
            'delivery_zone_id.required'    => 'ডেলিভারি জোন বেছে নিন।',
            'delivery_location_id.required' => 'ডেলিভারি এলাকা বেছে নিন।',
            'payment_method.in'            => 'একটি বৈধ পেমেন্ট পদ্ধতি বেছে নিন।',
            'sender_number.required'       => 'সেন্ডার নম্বর দিন।',
            'transaction_id.required'      => 'ট্রানজেকশন আইডি দিন।',
            'paid_amount.required'         => 'পেমেন্ট করা পরিমাণ দিন।',
            'bd_division_id.required'      => 'বিভাগ বেছে নিন।',
            'bd_district_id.required'      => 'জেলা বেছে নিন।',
            'bd_upazila_id.required'       => 'উপজেলা বেছে নিন।',
            'items.required'               => 'কমপক্ষে একটি পণ্য যোগ করুন।',
            'items.min'                    => 'কমপক্ষে একটি পণ্য যোগ করুন।',
            'items.max'                    => 'সর্বোচ্চ ২০টি পণ্য যোগ করা যাবে।',
        ]);

        // ── Verify BD address hierarchy (prevents tampered IDs from DevTools) ────
        $bdDivision = BdDivision::where('id', (int) $validated['bd_division_id'])
            ->where('is_active', true)->first();
        if (! $bdDivision) {
            return response()->json(['message' => 'বিভাগ পাওয়া যায়নি।', 'errors' => ['bd_division_id' => ['বিভাগ পাওয়া যায়নি।']]], 422);
        }

        $bdDistrict = BdDistrict::where('id', (int) $validated['bd_district_id'])
            ->where('division_id', $bdDivision->id)
            ->where('is_active', true)->first();
        if (! $bdDistrict) {
            return response()->json(['message' => 'জেলা পাওয়া যায়নি বা বিভাগের সাথে মিলছে না।', 'errors' => ['bd_district_id' => ['জেলা পাওয়া যায়নি।']]], 422);
        }

        $bdUpazila = BdUpazila::where('id', (int) $validated['bd_upazila_id'])
            ->where('district_id', $bdDistrict->id)
            ->where('is_active', true)->first();
        if (! $bdUpazila) {
            return response()->json(['message' => 'উপজেলা পাওয়া যায়নি বা জেলার সাথে মিলছে না।', 'errors' => ['bd_upazila_id' => ['উপজেলা পাওয়া যায়নি।']]], 422);
        }

        $bdUnion = null;
        if (! empty($validated['bd_union_id'])) {
            $bdUnion = BdUnion::where('id', (int) $validated['bd_union_id'])
                ->where('upazila_id', $bdUpazila->id)
                ->where('is_active', true)->first();
            if (! $bdUnion) {
                return response()->json(['message' => 'ইউনিয়ন পাওয়া যায়নি বা উপজেলার সাথে মিলছে না।', 'errors' => ['bd_union_id' => ['ইউনিয়ন পাওয়া যায়নি।']]], 422);
            }
        }

        // ── Backend price recalculation (never trust frontend prices) ──────────
        $priceSettings  = PriceSetting::current();
        $subtotal       = 0.0;
        $processedItems = [];
        $orderComboId   = null;
        $orderType      = 'custom';

        if ($isComboOrder) {
            // Fixed combo branch — derive everything from DB
            $combo = Combo::with('items.product')
                ->where('id', (int) $validated['combo_id'])
                ->where('is_active', true)
                ->first();

            if (! $combo) {
                return response()->json([
                    'message' => 'কম্বোটি পাওয়া যায়নি বা নিষ্ক্রিয়।',
                    'errors'  => ['items' => ['কম্বো পাওয়া যায়নি।']],
                ], 422);
            }

            $subtotal     = (float) $combo->sell_price;
            $orderComboId = $combo->id;
            $orderType    = 'fixed_combo';

            foreach ($combo->items as $item) {
                $processedItems[] = [
                    'product_id'    => $item->product_id,
                    'product_name'  => $item->product?->name_bn ?? '',
                    'quantity_gram' => $item->quantity_gram,
                    'unit_price'    => (float) $item->unit_price,
                    'line_total'    => (float) $item->line_total,
                ];
            }
        } else {
            // Custom / single product branch
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

            $orderType = count($processedItems) === 1 ? 'single_product' : 'custom';
        }

        // ── Backend delivery charge — zone/location lookup, never trust frontend ─
        $packagingCost  = (float) $priceSettings->default_packaging_cost;
        $minOrderAmount = (float) $priceSettings->minimum_order_amount;

        $zone = DeliveryZone::where('id', (int) $validated['delivery_zone_id'])
            ->where('is_active', true)
            ->first();

        if (! $zone) {
            return response()->json([
                'message' => 'ডেলিভারি জোন পাওয়া যায়নি বা নিষ্ক্রিয়।',
                'errors'  => ['delivery_zone_id' => ['ডেলিভারি জোন পাওয়া যায়নি।']],
            ], 422);
        }

        $location = DeliveryLocation::where('id', (int) $validated['delivery_location_id'])
            ->where('zone_id', $zone->id)
            ->where('is_active', true)
            ->first();

        if (! $location) {
            return response()->json([
                'message' => 'ডেলিভারি এলাকা পাওয়া যায়নি বা নিষ্ক্রিয়।',
                'errors'  => ['delivery_location_id' => ['ডেলিভারি এলাকা পাওয়া যায়নি।']],
            ], 422);
        }

        $deliveryCharge = $zone->chargeFor($location, $subtotal);
        $grandTotal     = $subtotal + $packagingCost + $deliveryCharge;

        if (! $isComboOrder && $grandTotal < $minOrderAmount) {
            return response()->json([
                'message' => 'ন্যূনতম অর্ডার পরিমাণ ৳' . number_format($minOrderAmount, 0) . '।',
                'errors'  => ['items' => ['ন্যূনতম অর্ডার পরিমাণ ৳' . number_format($minOrderAmount, 0) . '। আরো পণ্য যোগ করুন।']],
            ], 422);
        }

        // ── Aggregate required grams per product ────────────────────────────────
        $neededByProduct = [];
        foreach ($processedItems as $item) {
            $pid = $item['product_id'];
            $neededByProduct[$pid] = ($neededByProduct[$pid] ?? 0) + $item['quantity_gram'];
        }

        // Pre-check stock (fast-fail before acquiring locks)
        foreach ($neededByProduct as $productId => $neededGram) {
            $product = Product::find($productId);
            if (! $product || $product->stock * 1000 < $neededGram) {
                $name = $product?->name_bn ?? 'পণ্যটি';
                return response()->json([
                    'message' => 'দুঃখিত, ' . $name . '-এর পর্যাপ্ত স্টক নেই। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।',
                    'errors'  => ['items' => ['পণ্যটির পর্যাপ্ত স্টক নেই।']],
                ], 422);
            }
        }

        // ── Generate unique order number ────────────────────────────────────────
        do {
            $orderNumber = 'MSL-' . date('Ymd') . '-' . strtoupper(Str::random(5));
        } while (Order::where('order_number', $orderNumber)->exists());

        // ── Persist order + items + stock deduction in a transaction ────────────
        try {
            $order = DB::transaction(function () use (
                $validated, $processedItems, $subtotal, $packagingCost, $deliveryCharge,
                $grandTotal, $orderNumber, $isManualPayment, $orderType, $zone, $location,
                $orderComboId, $bdDivision, $bdDistrict, $bdUpazila, $bdUnion, $neededByProduct
            ) {
                // Re-validate stock with row-level locks to prevent race conditions
                foreach ($neededByProduct as $productId => $neededGram) {
                    $product = Product::lockForUpdate()->find($productId);
                    if (! $product || $product->stock * 1000 < $neededGram) {
                        $name = $product?->name_bn ?? 'পণ্যটি';
                        throw new \RuntimeException($name . '-এর পর্যাপ্ত স্টক নেই। পেজ রিফ্রেশ করে আবার চেষ্টা করুন।');
                    }
                }

                $order = Order::create([
                    'order_number'          => $orderNumber,
                    'customer_name'         => $validated['full_name'],
                    'mobile_number'         => $validated['mobile_number'],
                    'alternative_number'    => $validated['alternative_number'] ?? null,
                    'full_address'          => $validated['full_address'],
                    'district'              => $zone->zone_name,
                    'area'                  => $location->location_name,
                    'delivery_area'         => $zone->zone_type,
                    'delivery_zone_id'      => $zone->id,
                    'delivery_location_id'  => $location->id,
                    'delivery_zone_name'    => $zone->zone_name,
                    'delivery_location_name' => $location->location_name,
                    'order_note'            => $validated['order_note'] ?? null,
                    'order_type'            => $orderType,
                    'subtotal'              => $subtotal,
                    'packaging_cost'        => $packagingCost,
                    'delivery_charge'       => $deliveryCharge,
                    'grand_total'           => $grandTotal,
                    'payment_method'        => $validated['payment_method'],
                    'sender_number'         => $isManualPayment ? ($validated['sender_number'] ?? null) : null,
                    'transaction_id'        => $isManualPayment ? ($validated['transaction_id'] ?? null) : null,
                    'paid_amount'           => $isManualPayment ? ($validated['paid_amount'] ?? null) : null,
                    'payment_status'        => 'pending',
                    'order_status'          => 'pending',
                    'accepts_marketing'     => false,
                    'combo_id'              => $orderComboId,
                    'bd_division_id'        => $bdDivision->id,
                    'bd_district_id'        => $bdDistrict->id,
                    'bd_upazila_id'         => $bdUpazila->id,
                    'bd_union_id'           => $bdUnion?->id,
                    'division_name'         => $bdDivision->bn_name,
                    'district_name'         => $bdDistrict->bn_name,
                    'upazila_name'          => $bdUpazila->bn_name,
                    'union_name'            => $bdUnion?->bn_name,
                ]);

                foreach ($processedItems as $item) {
                    $order->items()->create($item);
                }

                // Deduct stock (ceil to whole kg)
                foreach ($neededByProduct as $productId => $neededGram) {
                    Product::where('id', $productId)
                        ->decrement('stock', (int) ceil($neededGram / 1000));
                }

                $order->update(['stock_deducted_at' => now()]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'errors'  => ['items' => [$e->getMessage()]],
            ], 422);
        }

        if ($request->hasFile('payment_screenshot')) {
            $path = $request->file('payment_screenshot')
                ->store('payment-screenshots', 'public');
            $order->update(['payment_screenshot' => $path]);
        }

        $this->upsertCustomer($order, $request->boolean('accepts_marketing'));

        return response()->json([
            'success'  => true,
            'redirect' => route('order.success', $order->order_number),
        ]);
    }

    private function upsertCustomer(Order $order, bool $acceptsMarketing): void
    {
        try {
            $addressData = [
                'last_division_name' => $order->division_name,
                'last_district_name' => $order->district_name,
                'last_upazila_name'  => $order->upazila_name,
                'last_union_name'    => $order->union_name,
                'last_full_address'  => $order->full_address,
                'last_order_at'      => $order->created_at,
            ];

            $existing = Customer::where('mobile_number', $order->mobile_number)->first();

            if ($existing) {
                Customer::where('id', $existing->id)->update(array_merge($addressData, [
                    'name'               => $order->customer_name,
                    'alternative_number' => $order->alternative_number ?? $existing->alternative_number,
                    'accepts_marketing'  => $existing->accepts_marketing || $acceptsMarketing,
                    'total_orders'       => DB::raw('total_orders + 1'),
                    'total_spent'        => DB::raw('total_spent + ' . (float) $order->grand_total),
                ]));
                $order->update(['customer_id' => $existing->id, 'accepts_marketing' => $existing->accepts_marketing || $acceptsMarketing]);
            } else {
                $customer = Customer::create(array_merge($addressData, [
                    'name'               => $order->customer_name,
                    'mobile_number'      => $order->mobile_number,
                    'alternative_number' => $order->alternative_number,
                    'total_orders'       => 1,
                    'total_spent'        => (float) $order->grand_total,
                    'accepts_marketing'  => $acceptsMarketing,
                ]));
                $order->update(['customer_id' => $customer->id, 'accepts_marketing' => $acceptsMarketing]);
            }
        } catch (\Exception) {
            // Non-critical — order is already placed successfully
        }
    }

    public function success(string $orderNumber)
    {
        $order = Order::with('items')
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        $whatsappNumber = WebsiteSetting::get('whatsapp_number');
        $siteName       = WebsiteSetting::get('site_name', 'মসলা ঘর');

        return view('order-success', compact('order', 'whatsappNumber', 'siteName'));
    }
}
