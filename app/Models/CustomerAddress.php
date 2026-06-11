<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerAddress extends Model
{
    protected $fillable = [
        'user_id', 'label', 'name', 'phone',
        'division_name', 'district_name', 'upazila_name', 'union_name',
        'full_address', 'is_default',
        'delivery_zone_id', 'delivery_location_id', 'delivery_area',
        'bd_division_id', 'bd_district_id', 'bd_upazila_id', 'bd_union_id',
    ];

    protected $casts = ['is_default' => 'boolean'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deliveryZone(): BelongsTo
    {
        return $this->belongsTo(DeliveryZone::class, 'delivery_zone_id');
    }

    public function deliveryLocation(): BelongsTo
    {
        return $this->belongsTo(DeliveryLocation::class, 'delivery_location_id');
    }

    /** True when the address carries the zone+location needed to compute delivery charge. */
    public function isCheckoutReady(): bool
    {
        return $this->delivery_zone_id && $this->delivery_location_id;
    }

    /** Compact one-line region label for summary cards. */
    public function regionLine(): string
    {
        return collect([$this->upazila_name, $this->district_name, $this->division_name])
            ->filter()->implode(', ');
    }
}
