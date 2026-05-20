<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'slug',
        'origin',
        'grade',
        'size_label',
        'image',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
