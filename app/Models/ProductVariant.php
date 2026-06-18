<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
    // WooCommerce-style simple variant: name + optional image, retail/sale
    // price, stock, sku, default flag, active flag. The legacy
    // origin/grade/size_label/slug columns remain in the table (nullable)
    // but are no longer set from the admin UI or frontend.
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'image',
        'retail_price',
        'sale_price',
        'stock',
        'sort_order',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'retail_price' => 'decimal:2',
        'sale_price'   => 'decimal:2',
        'stock'        => 'integer',
        'sort_order'   => 'integer',
        'is_active'    => 'boolean',
        'is_default'   => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Resolved image URL (local 'storage/...' or full http), null when none. */
    public function imageUrl(): ?string
    {
        if (! $this->image) {
            return null;
        }
        return Str::startsWith($this->image, 'http') ? $this->image : asset($this->image);
    }

    /**
     * Effective RETAIL price for this variant (sale price wins, else retail).
     * Null when the variant carries no own price — caller falls back to product.
     */
    public function effectiveRetailPrice(): ?float
    {
        if ($this->sale_price !== null && (float) $this->sale_price > 0) {
            return (float) $this->sale_price;
        }
        if ($this->retail_price !== null && (float) $this->retail_price > 0) {
            return (float) $this->retail_price;
        }
        return null;
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id')->orderBy('sort_order')->orderBy('quantity_gram');
    }

    public function activePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('quantity_gram');
    }

    public function activeRetailPrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id')
            ->where('sell_type', 'retail')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('quantity_gram');
    }

    public function activeWholesalePrices(): HasMany
    {
        return $this->hasMany(ProductPrice::class, 'product_variant_id')
            ->where('sell_type', 'wholesale')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('quantity_gram');
    }
}
