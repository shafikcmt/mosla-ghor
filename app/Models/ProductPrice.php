<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    protected $fillable = [
        'product_id',
        'label',
        'quantity_gram',
        'auto_price',
        'manual_price',
        'final_price',
        'is_manual_override',
        'is_active',
    ];

    protected $casts = [
        'quantity_gram'      => 'integer',
        'auto_price'         => 'decimal:2',
        'manual_price'       => 'decimal:2',
        'final_price'        => 'decimal:2',
        'is_manual_override' => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
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
