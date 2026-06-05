<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'vendor_id',
        'vendor_name',
        'sell_type',
        'price_id',
        'product_id',
        'product_name',
        'variant_name',
        'quantity_gram',
        'quantity',
        'unit',
        'unit_price',
        'discount_amount',
        'line_total',
    ];

    protected $casts = [
        'quantity_gram'   => 'integer',
        'quantity'        => 'decimal:3',
        'unit_price'      => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'line_total'      => 'decimal:2',
    ];

    /** Human display of quantity: free-form (qty + unit) or legacy grams. */
    public function quantityLabel(): string
    {
        if ($this->quantity !== null) {
            $qty = rtrim(rtrim(number_format((float) $this->quantity, 3, '.', ''), '0'), '.');
            return $qty . ' ' . ($this->unit ?: '');
        }
        if ($this->quantity_gram) {
            return $this->quantity_gram >= 1000
                ? rtrim(rtrim(number_format($this->quantity_gram / 1000, 2), '0'), '.') . ' কেজি'
                : $this->quantity_gram . ' গ্রাম';
        }
        return '1';
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
