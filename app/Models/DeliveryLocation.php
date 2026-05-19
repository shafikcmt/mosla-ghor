<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryLocation extends Model
{
    protected $fillable = [
        'zone_id',
        'location_name',
        'keywords',
        'delivery_charge',
        'is_active',
    ];

    protected $casts = [
        'delivery_charge' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function zone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'zone_id');
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function effectiveCharge(): float
    {
        return $this->delivery_charge !== null
            ? (float) $this->delivery_charge
            : (float) $this->zone->delivery_charge;
    }
}
