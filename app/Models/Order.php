<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'customer_name',
        'mobile_number',
        'alternative_number',
        'full_address',
        'district',
        'area',
        'order_note',
        'order_type',
        'subtotal',
        'packaging_cost',
        'delivery_charge',
        'grand_total',
        'payment_method',
        'payment_status',
        'order_status',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'packaging_cost'  => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'grand_total'     => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
