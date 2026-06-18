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
        'name_bn',
        'name_en',
        'slug',
        'category_id',
        'sku',
        'category',
        'brand',
        'unit',
        'main_image',
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
        'is_wholesale',
        'wholesale_enquiry_enabled',
        'min_order_quantity',
        'min_order_unit',
        'delivery_time',
        'payment_terms',
    ];

    protected $casts = [
        'gallery_images'            => 'array',
        'retail_price_1kg'          => 'decimal:2',
        'wholesale_price_1kg'       => 'decimal:2',
        'purchase_price'            => 'decimal:2',
        'selling_price'             => 'decimal:2',
        'stock'                     => 'integer',
        'stock_qty'                 => 'decimal:3',
        'low_stock_threshold'       => 'decimal:3',
        'sort_order'                => 'integer',
        'is_active'                 => 'boolean',
        'is_wholesale'              => 'boolean',
        'wholesale_enquiry_enabled' => 'boolean',
        'min_order_quantity'        => 'decimal:2',
    ];

    /** Units a vendor can pick for unit-managed products. */
    public const UNITS = ['kg', 'gram', 'pcs', 'bag', 'carton', 'packet'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
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

    // ── Wholesale (Paykari) ───────────────────────────────────────────────────
    /** A wholesale product hides its price publicly and is enquiry-only. */
    public function isWholesale(): bool
    {
        return (bool) $this->is_wholesale;
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

    // Create or refresh all standard pack-size rows in product_prices.
    // Rows with is_manual_override = true keep their final_price untouched.
    public function syncPrices(): void
    {
        $settings  = PriceSetting::current();
        $packSizes = [25, 50, 100, 250, 500, 1000];

        foreach ($packSizes as $grams) {
            $markup    = $settings->markupFor($grams);
            $autoPrice = round(($this->retail_price_1kg / 1000) * $grams * (1 + $markup / 100), 2);
            $existing  = $this->prices()->where('sell_type', 'retail')->where('quantity_gram', $grams)->first();

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
