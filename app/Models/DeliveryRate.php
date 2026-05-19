<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryRate extends Model
{
    protected $fillable = [
        'courier_id', 'delivery_zone_id', 'zone_type',
        'min_weight', 'max_weight',
        'courier_cost', 'customer_delivery_charge',
        'cod_percentage', 'return_charge', 'is_active',
    ];

    protected $casts = [
        'min_weight'                => 'integer',
        'max_weight'                => 'integer',
        'courier_cost'              => 'decimal:2',
        'customer_delivery_charge'  => 'decimal:2',
        'cod_percentage'            => 'decimal:2',
        'return_charge'             => 'decimal:2',
        'is_active'                 => 'boolean',
    ];

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public static function findBestRate(DeliveryZone $zone, int $weightGram, ?int $courierId = null): ?self
    {
        return static::where('is_active', true)
            ->where('min_weight', '<=', $weightGram)
            ->where('max_weight', '>=', $weightGram)
            ->where(function ($q) use ($zone) {
                $q->where('delivery_zone_id', $zone->id)
                  ->orWhere(function ($q2) use ($zone) {
                      $q2->whereNull('delivery_zone_id')
                         ->where('zone_type', $zone->zone_type);
                  });
            })
            ->when($courierId, fn($q) => $q->where('courier_id', $courierId))
            ->orderByRaw('delivery_zone_id IS NOT NULL DESC') // specific zone preferred over zone_type
            ->orderBy('customer_delivery_charge')
            ->first();
    }
}
