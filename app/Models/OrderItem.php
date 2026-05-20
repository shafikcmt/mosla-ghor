<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'sell_type',
        'price_id',
        'product_id',
        'product_name',
        'variant_name',
        'quantity_gram',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'quantity_gram' => 'integer',
        'unit_price'    => 'decimal:2',
        'line_total'    => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
