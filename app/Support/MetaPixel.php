<?php

namespace App\Support;

use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorMarketingSetting;
use Illuminate\Support\Facades\Auth;
use App\Models\ProductPrice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Meta (Facebook) Pixel — browser events (Phase A). Server-side copies of the
 * platform events go through MetaCapi (Phase B) with the same event ids.
 *
 * Rules enforced here, in one place:
 *  - Pixel IDs are digits only (10–20); anything else is never output.
 *  - Every event targets ONE pixel (rendered as fbq('trackSingle', …)).
 *  - A vendor pixel only ever receives that vendor's own products and values.
 *  - Platform scope 'own' = the platform pixel only receives admin products (vendor_id NULL).
 *  - Nothing is output for logged-in admins unless track_admin_users is on.
 *
 * CONTENT ID SCHEME (one identity for every funnel event — ViewContent, AddToWishlist,
 * AddToCart, InitiateCheckout, AddPaymentInfo, Purchase):
 *   content_type = 'product', id = "{product_id}-{product_prices.id}" (see contentId()).
 *   A product_prices row is the sellable pack (weight / variant), it is unique and stable.
 *   products.sku / product_variants.sku are NOT used: both are nullable and not unique,
 *   so they cannot identify a sellable item reliably. A future catalog feed must publish
 *   the same "{product_id}-{price_id}" as its id (SKU can go in a separate feed column).
 *   Fallback when no pack is known (wholesale enquiry Lead, products without a priced
 *   retail pack): the bare "{product_id}".
 *
 * QUANTITY: contents[].quantity = packs ordered, contents[].item_price = price of ONE pack,
 * value = Σ(item_price × quantity), num_items = Σ quantity. Purchase value is the
 * order grand total instead (what the customer pays, incl. delivery and discount).
 *
 * State lives in request attributes (not a singleton) so nothing leaks between requests.
 */
class MetaPixel
{
    public const ID_PATTERN = '/^\d{10,20}$/';
    public const CURRENCY = 'BDT';
    public const NEXT_PAGE_KEY = 'meta_pixel_next';
    public const PURCHASE_KEY = 'meta_pixel_purchase';
    public const CHECKOUT_KEY = 'meta_pixel_checkout';

    private const ATTR = 'meta_pixel.state';

    public static function validId(?string $id): bool
    {
        return is_string($id) && preg_match(self::ID_PATTERN, $id) === 1;
    }

    /** Read-only lookup (never creates the row on a storefront request). Fails closed. */
    public static function settings(): ?MarketingSetting
    {
        $state = self::state();
        if (! array_key_exists('settings', $state)) {
            try {
                $state['settings'] = MarketingSetting::query()->first();
            } catch (\Throwable $e) {
                Log::warning('Meta Pixel settings unavailable; pixel disabled for this request.', ['error' => get_class($e)]);
                $state['settings'] = null;
            }
            self::save($state);
        }
        return $state['settings'];
    }

    /** Tracking allowed for this visitor at all (admin exclusion). */
    public static function trackingAllowed(): bool
    {
        $settings = self::settings();
        if (! $settings) {
            return false;
        }
        if (Auth::user()?->is_admin && ! $settings->track_admin_users) {
            return false;
        }
        // Office / developer IPs (META_EXCLUDE_IPS) are internal traffic.
        $excluded = (array) config('services.meta.exclude_ips', []);
        if ($excluded && in_array((string) request()->ip(), $excluded, true)) {
            return false;
        }
        return true;
    }

    public static function active(): bool
    {
        if (self::state()['disabled'] ?? false) {
            return false;
        }
        return self::trackingAllowed() && (self::platformIds() !== [] || (bool) self::settings()?->vendor_pixels_enabled);
    }

    /** Turn the pixel off for the current page (e.g. maintenance pages that reuse the storefront layout). */
    public static function disableForPage(): void
    {
        $state = self::state();
        $state['disabled'] = true;
        self::save($state);
    }

    /** @return string[] */
    public static function platformIds(): array
    {
        $settings = self::settings();
        if (! $settings || ! $settings->pixel_enabled) {
            return [];
        }
        return array_values(array_unique(array_filter((array) $settings->pixel_ids, fn ($id) => self::validId((string) $id))));
    }

    public static function platformScopeOwn(): bool
    {
        return self::settings()?->platform_pixel_scope === 'own';
    }

    /** vendor_id => pixel_id for every vendor currently allowed to track. */
    public static function vendorPixels(): array
    {
        $state = self::state();
        if (! array_key_exists('vendors', $state)) {
            $state['vendors'] = [];
            if (self::trackingAllowed() && self::settings()?->vendor_pixels_enabled) {
                try {
                    $rows = VendorMarketingSetting::query()
                        ->where('pixel_enabled', true)->where('admin_blocked', false)->whereNotNull('pixel_id')
                        ->whereHas('vendor', fn ($q) => $q->where('status', 'approved')->where('is_active', true))
                        ->get(['vendor_id', 'pixel_id']);
                    foreach ($rows as $row) {
                        if (self::validId($row->pixel_id)) {
                            $state['vendors'][(int) $row->vendor_id] = $row->pixel_id;
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Meta Pixel vendor lookup failed.', ['error' => get_class($e)]);
                }
            }
            self::save($state);
        }
        return $state['vendors'];
    }

    public static function vendorPixelId(?int $vendorId): ?string
    {
        return $vendorId ? (self::vendorPixels()[$vendorId] ?? null) : null;
    }

    // ── Event queue ─────────────────────────────────────────────────────────

    /** Queue one single-pixel event for this page. */
    public static function add(string $pixelId, string $name, array $params, ?string $eventId = null, bool $vendor = false, bool $deferred = false): void
    {
        if (! self::validId($pixelId)) {
            return;
        }
        $state = self::state();
        $state['events'][] = array_filter([
            'pixel' => $pixelId, 'name' => $name, 'params' => $params, 'eventID' => $eventId, 'vendor' => $vendor,
            'deferred' => $deferred ?: null, // held by the browser until the customer acts (AddPaymentInfo)
        ], fn ($v) => $v !== null);
        self::save($state);
    }

    public static function events(): array
    {
        return self::state()['events'] ?? [];
    }

    /** Vendor pixel ids that must be initialised on this page (only those with events). */
    public static function vendorInits(): array
    {
        return array_values(array_unique(array_column(array_filter(self::events(), fn ($e) => ! empty($e['vendor'])), 'pixel')));
    }

    /** Register only products actually rendered by the current page. */
    public static function registerProduct(Product $product): void
    {
        self::registerItems([['product_id' => $product->id, 'vendor_id' => $product->vendor_id]]);
    }

    /** Register trusted, resolved cart/order items, retaining the item's vendor snapshot. */
    public static function registerItems(iterable $items): void
    {
        $state = self::state();
        foreach ($items as $item) {
            $productId = (int) data_get($item, 'product_id');
            $vendorId = (int) data_get($item, 'vendor_id');
            if ($productId && $vendorId) {
                $state['productVendors'][$productId] = $vendorId;
            }
        }
        self::save($state);
    }

    /** Register an order's items with the same vendor resolution Purchase uses. */
    public static function registerOrder(Order $order): void
    {
        self::registerItems(self::orderItems($order));
    }

    /**
     * Order lines for tracking. Fixed-combo orders store order_items.vendor_id = NULL
     * (OrderController builds combo items without it), so for those orders only the
     * owner is taken from the product. Analytics only: the order itself is never changed.
     */
    private static function orderItems(Order $order): array
    {
        $order->loadMissing('items');
        $isCombo = $order->combo_id || $order->order_type === 'fixed_combo';
        $owners = [];
        if ($isCombo) {
            $missing = $order->items->whereNull('vendor_id')->pluck('product_id')->filter()->unique()->all();
            if ($missing) {
                try {
                    $owners = Product::whereIn('id', $missing)->pluck('vendor_id', 'id')->all();
                } catch (\Throwable $e) {
                    Log::warning('Meta Pixel combo owner lookup failed.', ['error' => get_class($e)]);
                }
            }
        }

        return $order->items->map(fn ($i) => [
            'product_id' => $i->product_id, 'price_id' => $i->price_id,
            'vendor_id' => $i->vendor_id ?: ($owners[$i->product_id] ?? null),
            'line_total' => $i->line_total, 'unit_price' => $i->unit_price, // packs derived in lines()
        ])->all();
    }

    /**
     * Scope 'own' only: ids of admin-owned products (vendor_id NULL). The browser sends
     * AddToCart to the platform pixel only for these, so an item whose owner the page
     * didn't register can never leak a vendor product to the platform pixel (fail closed).
     * Contains product ids only — never vendor ids or vendor pixel ids.
     */
    private static function adminProductIds(): array
    {
        $state = self::state();
        if (! array_key_exists('adminProducts', $state)) {
            try {
                $state['adminProducts'] = Product::whereNull('vendor_id')->pluck('id')->map(fn ($id) => (int) $id)->all();
            } catch (\Throwable $e) {
                Log::warning('Meta Pixel admin product lookup failed.', ['error' => get_class($e)]);
                $state['adminProducts'] = [];
            }
            self::save($state);
        }
        return $state['adminProducts'];
    }

    /** Config for client-side AddToCart: only what the browser needs to route items correctly. */
    public static function clientConfig(): array
    {
        $own = self::platformScopeOwn();
        $productVendors = self::state()['productVendors'] ?? [];
        $vendors = $productVendors
            ? array_intersect_key(self::vendorPixels(), array_flip(array_values($productVendors)))
            : [];
        if (! $own) {
            $productVendors = array_filter($productVendors, fn ($vendorId) => isset($vendors[$vendorId]));
        }

        $config = [
            'platform' => self::platformIds(),
            'own' => $own,
            'vendors' => (object) $vendors,
            'productVendors' => (object) $productVendors,
            'currency' => self::CURRENCY,
            // Browser may mirror AddToCart / Contact to the server (Conversions API) — a flag only, never a token.
            'capi' => MetaCapi::enabled(),
            'endpoints' => ['cart' => route('meta.add-to-cart'), 'contact' => route('meta.contact')],
        ];
        if ($own && $config['platform']) {
            $config['adminProducts'] = self::adminProductIds();
        }

        return $config;
    }

    // ── Event builders ──────────────────────────────────────────────────────

    /** Unique id for one logical event; the SAME value goes to the browser and to the Conversions API. */
    public static function newEventId(string $prefix): string
    {
        return $prefix.'-'.Str::lower(Str::random(20));
    }

    /** The single content identity used by every funnel event (see class doc). */
    public static function contentId(int $productId, ?int $priceId = null): string
    {
        return $priceId ? $productId.'-'.$priceId : (string) $productId;
    }

    /** The pack shown as "from" price on the product page: cheapest active direct retail pack. */
    public static function defaultPack(Product $product): ?ProductPrice
    {
        try {
            return $product->prices()->where('is_active', true)->whereNull('product_variant_id')
                ->where('sell_type', 'retail')->orderBy('final_price')->orderBy('id')->first();
        } catch (\Throwable) {
            return null;
        }
    }

    /** Single-product params (ViewContent, AddToWishlist). Price is for ONE pack; quantity is 1. */
    public static function productParams(Product $product, ?float $price = null, ?int $priceId = null): array
    {
        $id = self::contentId($product->id, $priceId);
        $content = ['id' => $id, 'quantity' => 1];
        if ($price !== null) {
            $content['item_price'] = round($price, 2);
        }
        return array_filter([
            'content_ids' => [$id],
            'content_type' => 'product',
            'contents' => [$content],
            'content_name' => $product->display_name,
            'value' => $price !== null ? round($price, 2) : null,
            'currency' => self::CURRENCY,
        ], fn ($v) => $v !== null);
    }

    /** ViewContent: one event id shared by the browser pixel(s) and the Conversions API. */
    public static function viewContent(Product $product, ?float $value = null, ?int $priceId = null): void
    {
        self::registerProduct($product);
        if (! self::active()) {
            return;
        }
        $state = self::state();
        if (isset($state['viewed'][$product->id])) {
            return; // one ViewContent per product per request, however many times the view calls this
        }
        $state['viewed'][$product->id] = true;
        self::save($state);

        $params = self::productParams($product, $value, $value !== null ? $priceId : null);
        $eventId = self::newEventId('vc');

        self::toProductOwners($product->vendor_id, 'ViewContent', $params, $eventId);
        MetaCapi::viewContent($product, $params, $eventId);
    }

    /** AddToWishlist after a wishlist row was really created; fires on the next page (POST → redirect). */
    public static function addToWishlist(Product $product): void
    {
        $pack = self::defaultPack($product);
        $params = self::productParams($product, $pack ? (float) $pack->final_price : null, $pack?->id);
        $eventId = self::newEventId('wl');
        self::queueNextPage('AddToWishlist', $params, $eventId, $product->vendor_id, true);
        MetaCapi::addToWishlist($product, $params, $eventId);
    }

    /** Resolved server-side lines for one pack × quantity (used by the AddToCart server copy). */
    public static function lineForPack(ProductPrice $price, int $qty): array
    {
        return self::lines([[
            'product_id' => $price->product_id, 'price_id' => $price->id, 'vendor_id' => $price->product?->vendor_id,
            'quantity' => $qty, 'unit_price' => $price->final_price, 'line_total' => (float) $price->final_price * $qty,
        ]])[0] ?? [];
    }

    /** Platform-pixel view of resolved lines (scope 'own' drops vendor lines). */
    public static function platformLines(array $lines): array
    {
        return array_values(self::platformScopeOwn() ? array_filter($lines, fn ($l) => $l['vendor_id'] === null) : $lines);
    }

    /** Public wrapper so the Conversions API shares the exact browser cart shape. */
    public static function cartParamsFor(array $lines): array
    {
        return self::cartParams($lines);
    }

    /**
     * InitiateCheckout — the customer reached the order-review step. Browser + Conversions API,
     * one shared event id. A refresh / back-navigation with an unchanged cart inside 30 minutes
     * is ignored (session fingerprint), and the fingerprint is cleared when an order is placed.
     */
    public static function initiateCheckout(iterable $items): void
    {
        $items = is_array($items) ? $items : iterator_to_array($items);
        self::registerItems($items);
        if (! self::active()) {
            return;
        }
        $lines = self::lines($items);
        if (! $lines) {
            return;
        }

        $fingerprint = md5(json_encode(array_map(fn ($l) => [$l['id'], $l['quantity'], $l['line_total']], $lines)));
        $seen = session(self::CHECKOUT_KEY);
        if (is_array($seen) && ($seen['fp'] ?? null) === $fingerprint && time() - (int) ($seen['at'] ?? 0) < 1800) {
            return;
        }
        session([self::CHECKOUT_KEY => ['fp' => $fingerprint, 'at' => time()]]);

        $eventId = self::newEventId('ic');
        self::emitCart('InitiateCheckout', $lines, $eventId, false);
        if ($platformLines = self::platformLines($lines)) {
            MetaCapi::cartEvent('InitiateCheckout', $eventId, self::cartParams($platformLines));
        }
    }

    /**
     * AddPaymentInfo — queued as DEFERRED: the browser sends it only when the customer submits the
     * payment step with a chosen method (MoslaPixel.fire('AddPaymentInfo')), never on page view.
     * Browser only (no server copy: the server cannot see the intent before the order POST).
     */
    public static function paymentInfo(iterable $items): void
    {
        $items = is_array($items) ? $items : iterator_to_array($items);
        self::registerItems($items);
        if (! self::active()) {
            return;
        }
        if ($lines = self::lines($items)) {
            self::emitCart('AddPaymentInfo', $lines, self::newEventId('pi'), true);
        }
    }

    /** Queue one cart-shaped event per allowed pixel, platform per scope and each vendor with its own lines. */
    private static function emitCart(string $name, array $lines, string $eventId, bool $deferred): void
    {
        if ($platformLines = self::platformLines($lines)) {
            foreach (self::platformIds() as $id) {
                self::add($id, $name, self::cartParams($platformLines), $eventId, false, $deferred);
            }
        }
        foreach (self::byVendor($lines) as $vendorId => $vendorLines) {
            self::add(self::vendorPixelId($vendorId), $name, self::cartParams($vendorLines), $eventId.'-v'.$vendorId, true, $deferred);
        }
    }

    /**
     * Purchase for the success page. Call only when the one-time session flag matched,
     * so a refresh or a stranger opening the URL never fires it.
     */
    public static function purchase(Order $order): void
    {
        if (! self::active()) {
            return;
        }
        $lines = self::lines(self::orderItems($order));

        if ($params = self::platformPurchaseParams($order, $lines)) {
            foreach (self::platformIds() as $id) {
                self::add($id, 'Purchase', $params, (string) $order->order_number);
            }
        }

        $touched = [];
        foreach (self::byVendor($lines) as $vendorId => $vendorLines) {
            self::add(self::vendorPixelId($vendorId), 'Purchase', self::cartParams($vendorLines) + ['order_id' => (string) $order->order_number],
                $order->order_number.'-'.$vendorId, true);
            $touched[] = $vendorId;
        }
        if ($touched) {
            try {
                VendorMarketingSetting::whereIn('vendor_id', $touched)->update(['last_event_at' => now()]);
            } catch (\Throwable) {
                // tracking metadata only
            }
        }
    }

    /**
     * Platform-pixel Purchase params for an order, or null when scope 'own' leaves no
     * admin items. Shared by the browser event and the Conversions API so both carry
     * the same value (scope all = grand total; scope own = admin items only).
     */
    public static function platformPurchaseParams(Order $order, ?array $lines = null): ?array
    {
        $lines ??= self::lines(self::orderItems($order));

        if (self::platformScopeOwn()) {
            $platformLines = array_filter($lines, fn ($l) => $l['vendor_id'] === null);
            $platformValue = null; // sum of admin items (from cartParams)
        } else {
            $platformLines = $lines;
            $platformValue = (float) $order->grand_total;
        }
        if (! $platformLines) {
            return null;
        }
        $params = self::cartParams($platformLines);
        if ($platformValue !== null) {
            // Purchase value = what the customer pays (grand total); contents keep per-pack prices.
            $params['value'] = round($platformValue, 2);
        }
        $params['order_id'] = (string) $order->order_number; // reference besides the event_id
        return $params;
    }

    public static function productLeadParams(Product $product): array
    {
        return [
            'content_ids' => [self::contentId($product->id)], 'content_type' => 'product',
            'content_name' => $product->display_name, 'currency' => self::CURRENCY,
        ];
    }

    /** May the platform pixel receive an event about this product (scope 'own' excludes vendor products)? */
    public static function platformAllowsProduct(?int $vendorId): bool
    {
        return ! (self::platformScopeOwn() && $vendorId);
    }

    /** Platform-visible product ids of a combo enquiry (product_id => vendor_id|null). */
    public static function platformComboIds(array $productVendorIds): array
    {
        $ids = [];
        foreach ($productVendorIds as $productId => $vendorId) {
            if (self::platformAllowsProduct($vendorId)) {
                $ids[] = self::contentId((int) $productId);
            }
        }
        return $ids;
    }

    /** Lead for a single-product enquiry (platform per scope + the product's vendor). */
    public static function leadForProduct(Product $product, string $eventId): void
    {
        self::queueNextPage('Lead', self::productLeadParams($product), $eventId, $product->vendor_id, true);
    }

    /** Lead for a multi-product (combo) enquiry: platform pixel only, items filtered by scope. */
    public static function leadForCombo(array $productVendorIds, string $eventId): void
    {
        if ($ids = self::platformComboIds($productVendorIds)) {
            self::queueNextPage('Lead', ['content_ids' => $ids, 'content_type' => 'product', 'currency' => self::CURRENCY], $eventId, null, false);
        }
    }

    public static function completeRegistration(string $eventId): void
    {
        self::queueNextPage('CompleteRegistration', ['status' => true], $eventId, null, false);
    }

    /**
     * Events that happen on a POST and must fire on the page after the redirect.
     * Pixel targets are resolved later, on that page, with the rules in force then.
     */
    public static function queueNextPage(string $name, array $params, string $eventId, ?int $vendorId, bool $platformRespectsScope): void
    {
        if (! self::active()) {
            return;
        }
        $queue = session(self::NEXT_PAGE_KEY, []);
        $queue[] = compact('name', 'params', 'eventId', 'vendorId', 'platformRespectsScope');
        session([self::NEXT_PAGE_KEY => array_slice($queue, -10)]);
    }

    /** Called by the events partial: moves queued next-page events onto this page. */
    public static function releaseNextPage(): void
    {
        if (! session()->has(self::NEXT_PAGE_KEY)) {
            return;
        }
        $queue = (array) session()->pull(self::NEXT_PAGE_KEY, []);
        if (! self::active()) {
            return;
        }
        foreach ($queue as $e) {
            $vendorId = isset($e['vendorId']) ? (int) $e['vendorId'] : null;
            if (! (($e['platformRespectsScope'] ?? false) && self::platformScopeOwn() && $vendorId)) {
                foreach (self::platformIds() as $id) {
                    self::add($id, $e['name'], (array) $e['params'], $e['eventId'] ?? null);
                }
            }
            if ($vendorId && ($pixel = self::vendorPixelId($vendorId))) {
                self::add($pixel, $e['name'], (array) $e['params'], ($e['eventId'] ?? '').'-v'.$vendorId, true);
            }
        }
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private static function toProductOwners(?int $vendorId, string $name, array $params, ?string $eventId = null): void
    {
        if (! (self::platformScopeOwn() && $vendorId)) {
            foreach (self::platformIds() as $id) {
                self::add($id, $name, $params, $eventId);
            }
        }
        if ($pixel = self::vendorPixelId($vendorId)) {
            self::add($pixel, $name, $params, $eventId ? $eventId.'-v'.$vendorId : null, true);
        }
    }

    private static function lines(iterable $items): array
    {
        $lines = [];
        foreach ($items as $item) {
            $item = is_array($item) ? $item : (array) $item;
            $productId = (int) ($item['product_id'] ?? 0);
            if (! $productId) {
                continue;
            }
            $priceId = $item['price_id'] ?? null;
            $lineTotal = (float) ($item['line_total'] ?? 0);
            $unit = (float) ($item['unit_price'] ?? 0);
            // Packs: explicit quantity/qty first; orders only store unit_price + line_total, so derive from those.
            $qty = (int) ($item['quantity'] ?? $item['qty'] ?? 0);
            if ($qty < 1) {
                $qty = ($unit > 0 && $lineTotal > 0) ? (int) round($lineTotal / $unit) : 1;
            }
            $qty = max(1, $qty);
            $lines[] = [
                'id' => self::contentId($productId, $priceId ? (int) $priceId : null),
                'vendor_id' => ! empty($item['vendor_id']) ? (int) $item['vendor_id'] : null,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'item_price' => $unit > 0 ? round($unit, 2) : round($lineTotal / $qty, 2),
            ];
        }
        return $lines;
    }

    /** @return array<int, array> vendor_id => lines, only vendors with an allowed pixel. */
    private static function byVendor(array $lines): array
    {
        $grouped = [];
        foreach ($lines as $line) {
            if ($line['vendor_id'] && self::vendorPixelId($line['vendor_id'])) {
                $grouped[$line['vendor_id']][] = $line;
            }
        }
        return $grouped;
    }

    private static function cartParams(array $lines): array
    {
        $lines = array_values($lines);
        return [
            'content_ids' => array_values(array_unique(array_column($lines, 'id'))),
            'content_type' => 'product',
            'contents' => array_map(fn ($l) => ['id' => $l['id'], 'quantity' => $l['quantity'], 'item_price' => $l['item_price']], $lines),
            'num_items' => array_sum(array_column($lines, 'quantity')),
            'value' => round(array_sum(array_column($lines, 'line_total')), 2),
            'currency' => self::CURRENCY,
        ];
    }

    private static function state(): array
    {
        return request()->attributes->get(self::ATTR, []);
    }

    private static function save(array $state): void
    {
        request()->attributes->set(self::ATTR, $state);
    }
}
