<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliverySetting extends Model
{
    protected $fillable = [
        'inside_dhaka_charge',
        'outside_dhaka_charge',
        'free_delivery_minimum_amount',
        'enable_free_delivery',
        'delivery_note',
    ];

    protected $casts = [
        'inside_dhaka_charge'          => 'decimal:2',
        'outside_dhaka_charge'         => 'decimal:2',
        'free_delivery_minimum_amount' => 'decimal:2',
        'enable_free_delivery'         => 'boolean',
    ];

    public static function current(): static
    {
        return static::firstOrCreate([], [
            'inside_dhaka_charge'  => 60,
            'outside_dhaka_charge' => 120,
            'enable_free_delivery' => false,
        ]);
    }

    public function chargeFor(string $area, float $subtotal): float
    {
        if ($this->enable_free_delivery
            && $this->free_delivery_minimum_amount !== null
            && $subtotal >= (float) $this->free_delivery_minimum_amount
        ) {
            return 0.0;
        }

        return $area === 'inside_dhaka'
            ? (float) $this->inside_dhaka_charge
            : (float) $this->outside_dhaka_charge;
    }

    public function isFreeDeliveryApplicable(float $subtotal): bool
    {
        return $this->enable_free_delivery
            && $this->free_delivery_minimum_amount !== null
            && $subtotal >= (float) $this->free_delivery_minimum_amount;
    }
}
