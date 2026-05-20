<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'product_variant_id',
        'sell_type',
        'label',
        'unit_name',
        'unit_value',
        'quantity_gram',
        'auto_price',
        'manual_price',
        'final_price',
        'compare_price',
        'is_manual_override',
        'is_active',
        'min_order_qty',
        'sort_order',
    ];

    protected $casts = [
        'quantity_gram'      => 'integer',
        'auto_price'         => 'decimal:2',
        'manual_price'       => 'decimal:2',
        'final_price'        => 'decimal:2',
        'compare_price'      => 'decimal:2',
        'is_manual_override' => 'boolean',
        'is_active'          => 'boolean',
        'min_order_qty'      => 'integer',
        'sort_order'         => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    // Scope: only active rows
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Recompute auto_price and final_price from the product's 1kg rate + settings
    public function recalculate(PriceSetting $settings): void
    {
        $markup = $settings->markupFor($this->quantity_gram);

        $this->auto_price = round(
            ($this->product->retail_price_1kg / 1000) * $this->quantity_gram * (1 + $markup / 100),
            2
        );

        if (! $this->is_manual_override) {
            $this->final_price = $settings->roundPrice($this->auto_price);
        }
    }

    // Human-readable weight label if label column is empty
    public function getWeightLabelAttribute(): string
    {
        return $this->label ?: $this->quantity_gram . 'g';
    }
}
