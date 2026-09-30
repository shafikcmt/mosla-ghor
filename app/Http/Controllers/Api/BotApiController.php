<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AuthenticateBotToken;
use App\Models\BotLead;
use App\Models\Combo;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\WebsiteSetting;
use App\Support\MetaCapi;
use App\Support\Phone;
use App\Support\ProductMedia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Read-mostly API for the separate social-media automation app (Messenger /
 * comment replies, daily post generation). Returns only public catalogue data
 * and minimal order status — never customer names, addresses or admin data.
 */
class BotApiController extends Controller
{
    private const ORDER_STATUS_LABELS = [
        'pending' => 'পেন্ডিং', 'confirmed' => 'নিশ্চিত', 'processing' => 'প্রসেসিং',
        'shipped' => 'কুরিয়ারে', 'delivered' => 'ডেলিভার্ড', 'cancelled' => 'বাতিল',
    ];

    public function ping(Request $request): JsonResponse
    {
        $token = $request->attributes->get(AuthenticateBotToken::ATTR);

        return response()->json([
            'ok' => true,
            'token' => $token->name,
            'abilities' => array_values((array) $token->abilities),
            'site' => ['name' => WebsiteSetting::siteName(), 'url' => url('/'), 'whatsapp' => WebsiteSetting::get('whatsapp_number') ?: null],
        ]);
    }

    /** GET products?q=&limit= — search by Bangla/English name, slug or SKU. */
    public function products(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = Product::active()->with(['activeRetailPrices.variant', 'category']);
        if ($q = trim((string) ($data['q'] ?? ''))) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $query->where(fn ($w) => $w->where('name_bn', 'like', $like)->orWhere('name_en', 'like', $like)
                ->orWhere('slug', 'like', $like)->orWhere('sku', 'like', $like));
        }

        return response()->json([
            'data' => $query->limit($data['limit'] ?? 10)->get()->map(fn ($p) => $this->productJson($p))->values(),
        ]);
    }

    public function product(int $id): JsonResponse
    {
        $product = Product::active()->with(['activeRetailPrices.variant', 'category'])->find($id);
        if (! $product) {
            return response()->json(['message' => 'Product not found.'], 404);
        }

        return response()->json(['data' => $this->productJson($product, true)]);
    }

    /** GET catalog/highlights — content ideas for daily posts. */
    public function highlights(): JsonResponse
    {
        $bestIds = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereNull('orders.deleted_at')
            ->where('orders.order_status', '!=', 'cancelled')
            ->where('orders.created_at', '>=', now()->subDays(30))
            ->whereNotNull('order_items.product_id')
            ->groupBy('order_items.product_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(20)
            ->pluck('order_items.product_id')
            ->all();

        $with = ['activeRetailPrices.variant', 'category'];
        $best = $bestIds
            ? Product::active()->with($with)->whereIn('id', $bestIds)->get()
                ->sortBy(fn ($p) => array_search($p->id, $bestIds))->take(8)
            : collect();
        $new = Product::active()->with($with)->reorder()->latest('id')->limit(8)->get();

        $combos = Combo::active()->whereNull('vendor_id')->with('items.product')->orderBy('sort_order')->limit(8)->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'badge' => $c->badge_text,
                'description' => $c->short_description,
                'price' => (float) $c->sell_price,
                'items' => $c->items->map(fn ($i) => array_filter([
                    'product' => $i->product?->display_name,
                    'quantity_gram' => $i->quantity_gram,
                ]))->values(),
                'url' => url('/#combo-builder'),
            ]);

        return response()->json([
            'best_sellers_30d' => $best->map(fn ($p) => $this->productJson($p))->values(),
            'new_arrivals' => $new->map(fn ($p) => $this->productJson($p))->values(),
            'combos' => $combos->values(),
        ]);
    }

    /**
     * GET orders/status?phone=&order_number= — latest 3 orders for the phone (or the
     * one matching order_number). Status fields only: no names, addresses or items.
     */
    public function orderStatus(Request $request): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'order_number' => ['nullable', 'string', 'max:50'],
        ]);
        $phone = Phone::normalize($data['phone']);
        if (! Phone::isValidBd($phone)) {
            return response()->json(['message' => 'সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।', 'errors' => ['phone' => ['invalid']]], 422);
        }

        $orders = Order::with('selectedCourier:id,name')->withCount('items')
            ->where('mobile_number', $phone)
            ->when($data['order_number'] ?? null, fn ($q, $n) => $q->where('order_number', $n))
            ->latest('id')->limit(3)->get();

        return response()->json([
            'data' => $orders->map(fn (Order $o) => [
                'order_number' => $o->order_number,
                'placed_at' => $o->created_at?->toIso8601String(),
                'status' => $o->order_status,
                'status_label' => self::ORDER_STATUS_LABELS[$o->order_status] ?? $o->order_status,
                'payment_status' => $o->payment_status,
                'grand_total' => (float) $o->grand_total,
                'items_count' => $o->items_count,
                'courier' => $o->selectedCourier?->name,
                'tracking_id' => $o->tracking_id,
                'sent_to_courier_at' => $o->sent_to_courier_at?->toIso8601String(),
                'delivered_at' => $o->delivered_at?->toIso8601String(),
            ])->values(),
            'track_url' => route('track-order'),
        ]);
    }

    /** POST leads — idempotent on (token, external_ref). */
    public function storeLead(Request $request): JsonResponse
    {
        $token = $request->attributes->get(AuthenticateBotToken::ATTR);
        $request->merge(['phone' => Phone::normalize($request->input('phone')) ?? $request->input('phone')]);

        $data = $request->validate([
            'source' => ['required', Rule::in(array_keys(BotLead::SOURCES))],
            'phone' => ['required', 'string', 'regex:/^01[3-9]\d{8}$/'],
            'message' => ['required', 'string', 'max:2000'],
            'customer_name' => ['nullable', 'string', 'max:100'],
            'external_ref' => ['nullable', 'string', 'max:100'],
            'page_name' => ['nullable', 'string', 'max:100'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'সঠিক মোবাইল নম্বর দিন (01XXXXXXXXX)।',
        ]);

        if (! empty($data['external_ref'])
            && ($existing = BotLead::where('bot_api_token_id', $token->id)->where('external_ref', $data['external_ref'])->first())) {
            return response()->json(['data' => ['id' => $existing->id, 'status' => $existing->status], 'duplicate' => true]);
        }

        $lead = BotLead::create($data + ['bot_api_token_id' => $token->id, 'status' => 'new']);
        Log::info('Bot lead received', ['lead_id' => $lead->id, 'source' => $lead->source, 'token_id' => $token->id]);

        MetaCapi::botLead($lead->load('product'));

        return response()->json(['data' => ['id' => $lead->id, 'status' => $lead->status], 'duplicate' => false], 201);
    }

    private function productJson(Product $p, bool $detail = false): array
    {
        $json = [
            'id' => $p->id,
            'name' => $p->display_name,
            'name_bn' => $p->name_bn,
            'name_en' => $p->name_en,
            'category' => ($cat = $p->cat) ? ($cat->name_bn ?: $cat->name_en) : null,
            'url' => route('products.show', $p->slug),
            'image' => ProductMedia::url($p->main_image),
            'in_stock' => $p->isInStock(),
            'is_wholesale' => $p->isWholesale(),
            'moq' => $p->moqLabel(),
            'packs' => $p->activeRetailPrices->map(fn (ProductPrice $pr) => [
                'price_id' => $pr->id,
                'label' => $pr->weight_label,
                'variant' => $pr->variant?->name,
                'price' => (float) $pr->final_price,
                'compare_price' => $pr->compare_price !== null ? (float) $pr->compare_price : null,
            ])->values(),
        ];
        if ($detail) {
            $json['short_description'] = $p->short_description ? strip_tags($p->short_description) : null;
            $json['delivery_time'] = $p->delivery_time;
        }
        return $json;
    }
}
