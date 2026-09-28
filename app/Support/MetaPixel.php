<?php

namespace App\Support;

use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use App\Models\VendorMarketingSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Meta (Facebook) Pixel — browser events (Phase A).
 *
 * Rules enforced here, in one place:
 *  - Pixel IDs are digits only (10–20); anything else is never output.
 *  - Every event targets ONE pixel (rendered as fbq('trackSingle', …)).
 *  - A vendor pixel only ever receives that vendor's own products and values.
 *  - Platform scope 'own' = the platform pixel only receives admin products (vendor_id NULL).
 *  - Nothing is output for logged-in admins unless track_admin_users is on.
 *
 * Content ids match the Phase C catalog feed: packs "{product_id}-{price_id}"
 * (content_type product); the product page uses the product id (product_group).
 *
 * State lives in request attributes (not a singleton) so nothing leaks between requests.
 */
class MetaPixel
{
    public const ID_PATTERN = '/^\d{10,20}$/';
    public const CURRENCY = 'BDT';
    public const NEXT_PAGE_KEY = 'meta_pixel_next';
    public const PURCHASE_KEY = 'meta_pixel_purchase';

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
    public static function add(string $pixelId, string $name, array $params, ?string $eventId = null, bool $vendor = false): void
    {
        if (! self::validId($pixelId)) {
            return;
        }
        $state = self::state();
        $state['events'][] = array_filter([
            'pixel' => $pixelId, 'name' => $name, 'params' => $params, 'eventID' => $eventId, 'vendor' => $vendor,
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

    /** Config for client-side AddToCart: only what the browser needs to route items correctly. */
    public static function clientConfig(): array
    {
        $productVendors = self::state()['productVendors'] ?? [];
        $vendors = $productVendors
            ? array_intersect_key(self::vendorPixels(), array_flip(array_values($productVendors)))
            : [];
        if (! self::platformScopeOwn()) {
            $productVendors = array_filter($productVendors, fn ($vendorId) => isset($vendors[$vendorId]));
        }

        return [
            'platform' => self::platformIds(),
            'own' => self::platformScopeOwn(),
            'vendors' => (object) $vendors,
            'productVendors' => (object) $productVendors,
            'currency' => self::CURRENCY,
        ];
    }

    // ── Event builders ──────────────────────────────────────────────────────

    public static function viewContent(Product $product, ?float $value = null): void
    {
        self::registerProduct($product);
        if (! self::active()) {
            return;
        }
        $params = array_filter([
            'content_ids' => [(string) $product->id],
            'content_type' => 'product_group',
            'content_name' => $product->display_name,
            'value' => $value !== null ? round($value, 2) : null,
            'currency' => self::CURRENCY,
        ], fn ($v) => $v !== null);

        self::toProductOwners($product->vendor_id, 'ViewContent', $params);
    }

    /**
     * Cart-shaped events (InitiateCheckout, AddPaymentInfo). $items: arrays/objects with
     * product_id, price_id, vendor_id, line_total and optional quantity.
     */
    public static function checkoutEvent(string $name, iterable $items): void
    {
        self::registerItems($items);
        if (! self::active()) {
            return;
        }
        $lines = self::lines($items);

        $platformLines = self::platformScopeOwn() ? array_filter($lines, fn ($l) => $l['vendor_id'] === null) : $lines;
        if ($platformLines) {
            foreach (self::platformIds() as $id) {
                self::add($id, $name, self::cartParams($platformLines));
            }
        }
        foreach (self::byVendor($lines) as $vendorId => $vendorLines) {
            self::add(self::vendorPixelId($vendorId), $name, self::cartParams($vendorLines), null, true);
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
        $order->loadMissing('items');
        $lines = self::lines($order->items->map(fn ($i) => [
            'product_id' => $i->product_id, 'price_id' => $i->price_id, 'vendor_id' => $i->vendor_id,
            'line_total' => $i->line_total, 'quantity' => 1,
        ]));

        if ($params = self::platformPurchaseParams($order, self::platformScopeOwn())) {
            foreach (self::platformIds() as $id) {
                self::add($id, 'Purchase', $params, (string) $order->order_number);
            }
        }

        $touched = [];
        foreach (self::byVendor($lines) as $vendorId => $vendorLines) {
            self::add(self::vendorPixelId($vendorId), 'Purchase', self::cartParams($vendorLines),
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

    /** Shared browser/server Purchase commerce data. No dispatch or request state. */
    public static function platformPurchaseParams(Order $order, bool $own): ?array
    {
        $order->loadMissing('items');
        $lines = self::lines($order->items->map(fn ($item) => [
            'product_id' => $item->product_id, 'price_id' => $item->price_id,
            'vendor_id' => $item->vendor_id, 'line_total' => $item->line_total, 'quantity' => 1,
        ]));
        if ($own) {
            $lines = array_filter($lines, fn ($line) => $line['vendor_id'] === null);
        }
        if (! $lines) {
            return null;
        }
        $params = self::cartParams($lines);
        if (! $own) {
            $params['value'] = round((float) $order->grand_total, 2);
        }

        return $params;
    }

    /** Lead for a single-product enquiry (platform per scope + the product's vendor). */
    public static function leadForProduct(Product $product, string $eventId): void
    {
        self::queueNextPage('Lead', [
            'content_ids' => [(string) $product->id], 'content_type' => 'product_group',
            'content_name' => $product->display_name, 'currency' => self::CURRENCY,
        ], $eventId, $product->vendor_id, true);
    }

    /** Lead for a multi-product (combo) enquiry: platform pixel only, items filtered by scope. */
    public static function leadForCombo(array $productVendorIds, string $eventId): void
    {
        $ids = [];
        foreach ($productVendorIds as $productId => $vendorId) {
            if (! self::platformScopeOwn() || $vendorId === null) {
                $ids[] = (string) $productId;
            }
        }
        if ($ids) {
            self::queueNextPage('Lead', ['content_ids' => $ids, 'content_type' => 'product_group', 'currency' => self::CURRENCY], $eventId, null, false);
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
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            $lineTotal = (float) ($item['line_total'] ?? 0);
            $lines[] = [
                'id' => $priceId ? $productId.'-'.(int) $priceId : (string) $productId,
                'vendor_id' => ! empty($item['vendor_id']) ? (int) $item['vendor_id'] : null,
                'quantity' => $qty,
                'line_total' => $lineTotal,
                'item_price' => round($lineTotal / $qty, 2),
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
