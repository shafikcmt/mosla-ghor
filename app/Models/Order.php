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
        'delivery_area',
        'delivery_zone_id',
        'delivery_location_id',
        'delivery_zone_name',
        'delivery_location_name',
        'order_note',
        'order_type',
        'subtotal',
        'packaging_cost',
        'delivery_charge',
        'grand_total',
        'payment_method',
        'sender_number',
        'transaction_id',
        'paid_amount',
        'payment_status',
        'order_status',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'packaging_cost'  => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'grand_total'     => 'decimal:2',
        'paid_amount'     => 'decimal:2',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
