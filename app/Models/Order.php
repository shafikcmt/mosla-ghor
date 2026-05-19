<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'mobile_number', 'alternative_number',
        'full_address', 'district', 'area', 'delivery_area',
        'delivery_zone_id', 'delivery_location_id', 'delivery_zone_name', 'delivery_location_name',
        'order_note', 'order_type', 'subtotal', 'packaging_cost', 'delivery_charge', 'grand_total',
        'payment_method', 'sender_number', 'transaction_id', 'paid_amount',
        'payment_status', 'order_status', 'combo_id', 'payment_screenshot',
        'bd_division_id', 'bd_district_id', 'bd_upazila_id', 'bd_union_id',
        'division_name', 'district_name', 'upazila_name', 'union_name',
        // Courier fields
        'selected_courier_id', 'suggested_courier_id', 'delivery_rate_id',
        'delivery_zone_type', 'weight_gram', 'courier_cost', 'cod_charge',
        'courier_status', 'tracking_id', 'consignment_id', 'courier_note',
        'sent_to_courier_at', 'delivered_at', 'returned_at',
        'delivery_charge_overridden', 'courier_cost_overridden', 'zone_overridden',
        'stock_deducted_at', 'stock_restored_at',
    ];

    protected $casts = [
        'subtotal'                   => 'decimal:2',
        'packaging_cost'             => 'decimal:2',
        'delivery_charge'            => 'decimal:2',
        'grand_total'                => 'decimal:2',
        'paid_amount'                => 'decimal:2',
        'courier_cost'               => 'decimal:2',
        'cod_charge'                 => 'decimal:2',
        'weight_gram'                => 'integer',
        'delivery_charge_overridden' => 'boolean',
        'courier_cost_overridden'    => 'boolean',
        'zone_overridden'            => 'boolean',
        'sent_to_courier_at'         => 'datetime',
        'delivered_at'               => 'datetime',
        'returned_at'                => 'datetime',
        'stock_deducted_at'          => 'datetime',
        'stock_restored_at'          => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function selectedCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'selected_courier_id');
    }

    public function suggestedCourier(): BelongsTo
    {
        return $this->belongsTo(Courier::class, 'suggested_courier_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function deliveryRate(): BelongsTo
    {
        return $this->belongsTo(DeliveryRate::class, 'delivery_rate_id');
    }
}
