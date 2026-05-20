<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Product extends Model
{
    protected $fillable = [
        'name_bn',
        'name_en',
        'slug',
        'main_image',
        'gallery_images',
        'video_url',
        'video_path',
        'short_description',
        'description',
        'retail_price_1kg',
        'wholesale_price_1kg',
        'stock',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'gallery_images'      => 'array',
        'retail_price_1kg'    => 'decimal:2',
        'wholesale_price_1kg' => 'decimal:2',
        'stock'               => 'integer',
        'sort_order'          => 'integer',
        'is_active'           => 'boolean',
    ];

    // All variants of this product (e.g., Iran Jira, Indian Jira)
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    // Active variants only
    public function activeVariants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->where('is_active', true)
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

    // Scope: only active products, sorted by sort_order
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    // Display name: prefer Bangla, fall back to English
    public function getDisplayNameAttribute(): string
    {
        return $this->name_bn ?: $this->name_en;
    }

    // Price per gram (base for automation logic)
    public function getPricePerGramAttribute(): float
    {
        return (float) $this->retail_price_1kg / 1000;
    }

    public function isInStock(): bool
    {
        return $this->stock > 0;
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
