<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryZone extends Model
{
    protected $fillable = [
        'zone_name',
        'zone_type',
        'delivery_charge',
        'free_delivery_minimum_amount',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'delivery_charge'              => 'decimal:2',
        'free_delivery_minimum_amount' => 'decimal:2',
        'is_active'                    => 'boolean',
        'sort_order'                   => 'integer',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class, 'zone_id');
    }

    public function activeLocations(): HasMany
    {
        return $this->hasMany(DeliveryLocation::class, 'zone_id')
            ->where('is_active', true)
            ->orderBy('location_name');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function chargeFor(DeliveryLocation $location, float $subtotal): float
    {
        // Free delivery check (zone-level)
        if ($this->free_delivery_minimum_amount !== null
            && $subtotal >= (float) $this->free_delivery_minimum_amount
        ) {
            return 0.0;
        }

        // Location charge overrides zone charge
        return $location->delivery_charge !== null
            ? (float) $location->delivery_charge
            : (float) $this->delivery_charge;
    }
}
