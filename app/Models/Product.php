<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $fillable = [
        'vendor_id',
        'approval_status',
        'rejection_reason',
        'name_bn',
        'name_en',
        'slug',
        'category_id',
        'sku',
        'category',
        'brand',
        'unit',
        'main_image',
        'wholesale_main_image',
        'gallery_images',
        'video_url',
        'video_path',
        'short_description',
        'description',
        'retail_price_1kg',
        'wholesale_price_1kg',
        'purchase_price',
        'selling_price',
        'stock',
        'stock_qty',
        'low_stock_threshold',
        'sort_order',
        'is_active',
        'show_in_retail',
        'show_in_wholesale',
        'is_wholesale',
        'wholesale_enquiry_enabled',
        'min_order_quantity',
        'min_order_unit',
        'unit_conversions',
        'delivery_time',
        'payment_terms',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'og_image',
        'canonical_url',
        'meta_robots',
        'main_image_alt',
        'wholesale_main_image_alt',
        'og_image_alt',
        'gallery_alts',
    ];

    /** Allowed values for the admin-only robots override. */
    public const META_ROBOTS = ['index,follow', 'noindex,follow', 'noindex,nofollow'];

    protected $casts = [
        'gallery_images'            => 'array',
        'gallery_alts'              => 'array',
        'unit_conversions'          => 'array',
        'retail_price_1kg'          => 'decimal:2',
        'wholesale_price_1kg'       => 'decimal:2',
        'purchase_price'            => 'decimal:2',
        'selling_price'             => 'decimal:2',
        'stock'                     => 'integer',
        'stock_qty'                 => 'decimal:3',
        'low_stock_threshold'       => 'decimal:3',
        'sort_order'                => 'integer',
        'is_active'                 => 'boolean',
        'show_in_retail'            => 'boolean',
        'show_in_wholesale'         => 'boolean',
        'is_wholesale'              => 'boolean',
        'wholesale_enquiry_enabled' => 'boolean',
        'min_order_quantity'        => 'decimal:2',
    ];

    /** Units a vendor can pick for unit-managed products. */
    public const UNITS = ['kg', 'gram', 'pcs', 'bag', 'carton', 'packet'];

    /** Bangla unit names (includes the legacy MOQ unit 'piece'). */
    public const UNIT_LABELS = ['kg' => 'কেজি', 'gram' => 'গ্রাম', 'pcs' => 'পিস', 'piece' => 'পিস', 'bag' => 'ব্যাগ', 'carton' => 'কার্টন', 'packet' => 'প্যাকেট'];

    public static function unitLabel(?string $unit): string
    {
        return self::UNIT_LABELS[$unit] ?? (string) $unit;
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->orderBy('name');
    }

    public function publicationStatus(): string
    {
        if ($this->vendor_id && $this->approval_status !== 'approved') {
            return $this->approval_status === 'rejected' ? 'অনুমোদিত নয় — অ্যাডমিনের সাথে যোগাযোগ করুন' : 'অ্যাডমিন অনুমোদনের অপেক্ষায়';
        }
        if (! $this->is_active) { return 'নিষ্ক্রিয় — ওয়েবসাইটে দেখাবে না'; }
        if (! $this->show_in_retail && ! $this->show_in_wholesale) { return 'বিক্রয় মাধ্যম নির্বাচন করুন'; }
        return 'প্রকাশিত — '.implode(' ও ', array_filter([$this->show_in_retail ? 'খুচরা' : null, $this->show_in_wholesale ? 'পাইকারি' : null]));
    }

    // Structured category (parent/child). NOTE: the legacy free-text `category`
    // string column shadows attribute access, so `$product->category` returns
    // that string. Use eager loading (with('category')) for this relation and
    // the `cat` accessor below to read the related Category model in views.
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Null-safe Category model accessor (works whether eager-loaded or not). */
    public function getCatAttribute(): ?Category
    {
        if ($this->relationLoaded('category')) {
            return $this->getRelation('category');
        }

        return $this->category_id ? $this->category()->first() : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->where('show_in_retail', true)->orWhere('show_in_wholesale', true))
            ->where(function ($q) {
                $q->whereNull('vendor_id')
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('vendor_id')
                         ->where('approval_status', 'approved');
                  });
            })
            ->orderBy('sort_order');
    }

    // All variants of this product (e.g., Iran Jira, Indian Jira)
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    // Active variants only — default variant first, then sort order.
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('sort_order');
    }

    // All pack sizes for this product
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->orderBy('quantity_gram');
    }

    // Only active pack sizes (all types), ordered by weight
    public function activePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('is_active', true)
            ->orderBy('quantity_gram');
    }

    // Only active retail prices
    public function activeRetailPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('sell_type', 'retail')
            ->where('is_active', true)
            ->orderBy('quantity_gram');
    }

    // Only active wholesale prices
    public function activeWholesalePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('sell_type', 'wholesale')
            ->where('is_active', true)
            ->orderBy('quantity_gram');
    }

    // Smallest active retail pack (cheapest entry point)
    public function smallestPack(): HasMany
    {
        return $this->hasMany(ProductPrice::class)
            ->where('sell_type', 'retail')
            ->where('is_active', true)
            ->orderBy('quantity_gram')
            ->limit(1);
    }

    // ── Reviews ──────────────────────────────────────────────────────────────
    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->latest();
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(ProductReview::class)->where('is_approved', true)->latest();
    }

    /** Average approved rating (0 when none), rounded to 1 decimal. */
    public function averageRating(): float
    {
        return round((float) $this->approvedReviews()->avg('rating'), 1);
    }

    public function reviewsCount(): int
    {
        return $this->approvedReviews()->count();
    }

    // Display name: prefer Bangla, fall back to English
    public function getDisplayNameAttribute(): string
    {
        return $this->name_bn ?: $this->name_en;
    }

    // ── SEO (custom value when set, otherwise the auto-generated fallback) ────
    /** Custom meta title, or the product name (the layout appends the site name). */
    public function seoTitle(): string
    {
        return trim((string) $this->meta_title) ?: $this->display_name;
    }

    public function seoDescription(): string
    {
        return trim((string) $this->meta_description)
            ?: \Illuminate\Support\Str::limit(strip_tags($this->short_description ?: $this->description ?: $this->display_name), 155);
    }

    public function seoKeywords(): ?string
    {
        return trim((string) $this->meta_keywords) ?: ($this->tags->pluck('name')->implode(', ') ?: null);
    }

    public function seoImage(): string
    {
        return \App\Support\ProductMedia::url($this->og_image)
            ?: (\App\Support\ProductMedia::url($this->main_image) ?: asset('images/product-placeholder.svg'));
    }

    // ── Image alt text (custom value when set, otherwise product-name fallback) ──
    public function mainImageAlt(): string
    {
        return trim((string) $this->main_image_alt) ?: $this->display_name;
    }

    /**
     * Cover photo for a showcase channel. পাইকারি uses its own cover when one is
     * uploaded, otherwise the normal main image. The gallery is shared by both.
     */
    public function coverImage(string $channel = 'retail'): ?string
    {
        return ($channel === 'wholesale' && filled($this->wholesale_main_image))
            ? $this->wholesale_main_image
            : $this->main_image;
    }

    public function coverImageAlt(string $channel = 'retail'): string
    {
        if ($channel === 'wholesale' && filled($this->wholesale_main_image)) {
            return trim((string) $this->wholesale_main_image_alt) ?: $this->mainImageAlt();
        }
        return $this->mainImageAlt();
    }

    public function ogImageAlt(): string
    {
        return trim((string) $this->og_image_alt) ?: $this->mainImageAlt();
    }

    /** Alt for any product image path: main, a gallery image (keyed by sha256 of its path), or fallback. */
    public function imageAlt(?string $path, int $position = 1): string
    {
        if ($path !== null && $path === $this->main_image) {
            return $this->mainImageAlt();
        }
        if ($path !== null && $path === $this->wholesale_main_image) {
            return $this->coverImageAlt('wholesale');
        }
        if ($path !== null) {
            $custom = trim((string) (($this->gallery_alts ?? [])[hash('sha256', $path)] ?? ''));
            if ($custom !== '') {
                return $custom;
            }
        }
        return $position > 1
            ? $this->display_name.' — ছবি '.\App\Support\UploadErrors::bn($position)
            : $this->display_name;
    }

    public function seoCanonical(): string
    {
        return trim((string) $this->canonical_url) ?: route('products.show', $this->slug);
    }

    // ── Wholesale (Paykari) ───────────────────────────────────────────────────
    /**
     * Wholesale-only product: shown solely on the পাইকারি listing, price hidden,
     * enquiry-only. A product visible in BOTH retail and wholesale is NOT
     * wholesale-only — it keeps its retail price/cart and defaults to retail.
     */
    public function isWholesale(): bool
    {
        return (bool) $this->show_in_wholesale && ! $this->show_in_retail;
    }

    // ── Unit conversion & per-unit পাইকারি price ─────────────────────────────
    /** Clean conversion rows: [['unit' => 'carton', 'qty' => 20.0, 'base' => 'kg'], …]. */
    public function unitConversionRows(): array
    {
        $rows = [];
        foreach ((array) ($this->unit_conversions ?? []) as $row) {
            if (is_array($row) && ! empty($row['unit']) && ! empty($row['base']) && (float) ($row['qty'] ?? 0) > 0) {
                $rows[] = ['unit' => (string) $row['unit'], 'qty' => (float) $row['qty'], 'base' => (string) $row['base']];
            }
        }
        return $rows;
    }

    /**
     * How many kg one $unit holds, following conversions (e.g. carton → packet → kg).
     * Null when the unit cannot be traced back to a weight.
     */
    public function unitInKg(string $unit, int $depth = 0): ?float
    {
        if ($unit === 'kg') { return 1.0; }
        if ($unit === 'gram') { return 0.001; }
        if ($depth > 4) { return null; }
        foreach ($this->unitConversionRows() as $row) {
            if ($row['unit'] === $unit && ($base = $this->unitInKg($row['base'], $depth + 1)) !== null) {
                return $row['qty'] * $base;
            }
        }
        return null;
    }

    public static function formatQty(float $qty): string
    {
        return rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.');
    }

    /** "1 কার্টন = 20 কেজি" style labels for every conversion row. */
    public function unitConversionLabels(): array
    {
        return array_map(fn ($r) => '1 '.self::unitLabel($r['unit']).' = '.self::formatQty($r['qty']).' '.self::unitLabel($r['base']),
            $this->unitConversionRows());
    }

    /** পাইকারি price per kg — null unless the product sells wholesale and a price is set. */
    public function wholesalePricePerKg(): ?float
    {
        return $this->show_in_wholesale && (float) $this->wholesale_price_1kg > 0 ? (float) $this->wholesale_price_1kg : null;
    }

    /** পাইকারি price for each converted unit (carton, bag, …) that traces back to kg. */
    public function wholesaleUnitPrices(): array
    {
        $perKg = $this->wholesalePricePerKg();
        if ($perKg === null) { return []; }
        $out = [];
        foreach ($this->unitConversionRows() as $row) {
            $kg = $this->unitInKg($row['unit']);
            if ($kg === null || isset($out[$row['unit']])) { continue; }
            $out[$row['unit']] = [
                'unit' => $row['unit'],
                'unit_label' => self::unitLabel($row['unit']),
                'kg' => round($kg, 3),
                'price' => round($perKg * $kg, 2),
            ];
        }
        return array_values($out);
    }

    /** Human MOQ label, e.g. "৫০ kg" — null when no MOQ is configured. */
    public function moqLabel(): ?string
    {
        if ($this->min_order_quantity === null || (float) $this->min_order_quantity <= 0) {
            return null;
        }

        $qty = rtrim(rtrim(number_format((float) $this->min_order_quantity, 2, '.', ''), '0'), '.');

        return $qty . ' ' . ($this->min_order_unit ?: 'kg');
    }

    // Price per gram (base for automation logic)
    public function getPricePerGramAttribute(): float
    {
        return (float) $this->retail_price_1kg / 1000;
    }

    public function isInStock(): bool
    {
        return $this->onHand() > 0;
    }

    // ── Stock helpers ────────────────────────────────────────────────────────
    // Legacy spice products store on-hand as whole-kg integer in `stock`.
    // Vendor unit-managed products (stock_qty not null) use the decimal `stock_qty`.

    public function stockMovements(): HasMany
    {
        return $this->hasMany(VendorStockMovement::class)->latest();
    }

    public function isUnitManaged(): bool
    {
        return $this->stock_qty !== null;
    }

    /** Canonical on-hand quantity in the product's own unit. */
    public function onHand(): float
    {
        return $this->isUnitManaged() ? (float) $this->stock_qty : (float) $this->stock;
    }

    /** Write a new on-hand value to the correct column (does not persist). */
    public function applyOnHand(float $value): void
    {
        if ($this->isUnitManaged()) {
            $this->stock_qty = round(max(0, $value), 3);
        } else {
            // Legacy kg-based products keep whole-kg integers.
            $this->stock = (int) max(0, round($value));
        }
    }

    public function stockUnit(): string
    {
        return $this->unit ?: 'kg';
    }

    public function stockStatus(): string
    {
        $onHand    = $this->onHand();
        $threshold = (float) $this->low_stock_threshold;

        if ($onHand <= 0) {
            return 'out_of_stock';
        }
        if ($threshold > 0 && $onHand <= $threshold) {
            return 'low_stock';
        }
        return 'in_stock';
    }

    public function isLowStock(): bool
    {
        return $this->stockStatus() === 'low_stock';
    }

    /** On-hand at/below threshold but still > 0 (works for both stock columns). */
    public function scopeLowStock($query)
    {
        return $query->where('low_stock_threshold', '>', 0)->where(function ($w) {
            $w->where(function ($a) {
                $a->whereNotNull('stock_qty')
                  ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
                  ->where('stock_qty', '>', 0);
            })->orWhere(function ($b) {
                $b->whereNull('stock_qty')
                  ->whereColumn('stock', '<=', 'low_stock_threshold')
                  ->where('stock', '>', 0);
            });
        });
    }

    public function scopeOutOfStock($query)
    {
        return $query->where(function ($w) {
            $w->where(function ($a) {
                $a->whereNotNull('stock_qty')->where('stock_qty', '<=', 0);
            })->orWhere(function ($b) {
                $b->whereNull('stock_qty')->where('stock', '<=', 0);
            });
        });
    }

    /**
     * Whether retail pack prices must be regenerated after a save: retail is on and
     * the product is new, retail was just switched on, the 1kg price changed, or the
     * product has no base retail packs yet. Shared by the full editor and quick edit.
     */
    public function shouldResyncPrices(bool $wasNew, bool $wasRetail, $oldPrice): bool
    {
        if (! $this->show_in_retail) {
            return false;
        }

        return $wasNew || ! $wasRetail || (float) $oldPrice !== (float) $this->retail_price_1kg
            || ! $this->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->exists();
    }

    // Create or refresh all standard pack-size rows in product_prices.
    // Rows with is_manual_override = true keep their final_price untouched.
    public function syncPrices(): void
    {
        $settings  = PriceSetting::current();
        $packSizes = [25, 50, 100, 250, 500, 1000];

        foreach ($packSizes as $grams) {
            $markup    = $settings->markupFor($grams);
            $autoPrice = round(($this->retail_price_1kg / 1000) * $grams * (1 + $markup / 100), 2);
            $existing  = $this->prices()->whereNull('product_variant_id')->where('sell_type', 'retail')->where('quantity_gram', $grams)->first();

            if ($existing) {
                $existing->auto_price = $autoPrice;
                if (! $existing->is_manual_override) {
                    $existing->final_price = $settings->roundPrice($autoPrice);
                }
                $existing->save();
            } else {
                $this->prices()->create([
                    'sell_type'          => 'retail',
                    'label'              => $this->packLabel($grams),
                    'quantity_gram'      => $grams,
                    'auto_price'         => $autoPrice,
                    'manual_price'       => null,
                    'final_price'        => $settings->roundPrice($autoPrice),
                    'is_manual_override' => false,
                    'is_active'          => true,
                ]);
            }
        }
    }

    private function packLabel(int $grams): string
    {
        return [
            25   => '২৫ গ্রাম',
            50   => '৫০ গ্রাম',
            100  => '১০০ গ্রাম',
            250  => '২৫০ গ্রাম',
            500  => '৫০০ গ্রাম',
            1000 => '১ কেজি',
        ][$grams] ?? $grams . 'g';
    }
}
