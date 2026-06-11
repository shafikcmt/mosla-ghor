<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    protected $fillable = [
        'bkash_number',
        'rocket_number',
        'nagad_number',
        'payment_instruction',
        'cash_on_delivery_enabled',
        'bkash_enabled',
        'rocket_enabled',
        'nagad_enabled',
        'instant_payment_enabled',
        'instant_discount_type',
        'instant_discount_value',
        'instant_min_order_amount',
        'cod_delivery_days',
        'instant_delivery_days',
    ];

    protected $casts = [
        'cash_on_delivery_enabled' => 'boolean',
        'bkash_enabled'            => 'boolean',
        'rocket_enabled'           => 'boolean',
        'nagad_enabled'            => 'boolean',
        'instant_payment_enabled'  => 'boolean',
        'instant_discount_value'   => 'decimal:2',
        'instant_min_order_amount' => 'decimal:2',
    ];

    public static function current(): static
    {
        // firstOrCreate persists the row immediately if it doesn't exist,
        // ensuring update() has a valid primary key to target.
        // firstOrNew() was the live bug: unsaved model → update() matched no rows.
        return static::firstOrCreate([], [
            'cash_on_delivery_enabled' => true,
            'bkash_enabled'            => false,
            'rocket_enabled'           => false,
            'nagad_enabled'            => false,
            'bkash_number'             => null,
            'rocket_number'            => null,
            'nagad_number'             => null,
            'payment_instruction'      => null,
            'instant_payment_enabled'  => true,
            'instant_discount_type'    => 'percentage',
            'instant_discount_value'   => 10,
            'instant_min_order_amount' => null,
            'cod_delivery_days'        => '৫–৭',
            'instant_delivery_days'    => '২–৩',
        ]);
    }

    /** Manual prepaid methods that are enabled (these power "Instant Payment"). */
    public function manualMethods(): array
    {
        return array_values(array_filter([
            $this->bkash_enabled  ? 'bkash'  : null,
            $this->nagad_enabled  ? 'nagad'  : null,
            $this->rocket_enabled ? 'rocket' : null,
        ]));
    }

    /** Instant Payment is offerable only if enabled AND a manual channel exists to pay through. */
    public function instantAvailable(): bool
    {
        return $this->instant_payment_enabled && ! empty($this->manualMethods());
    }

    /** Prepaid discount for a given subtotal (0 if below the optional minimum). */
    public function instantDiscountFor(float $subtotal): float
    {
        if ($this->instant_min_order_amount !== null && $subtotal < (float) $this->instant_min_order_amount) {
            return 0.0;
        }

        $discount = $this->instant_discount_type === 'fixed'
            ? (float) $this->instant_discount_value
            : round($subtotal * (float) $this->instant_discount_value / 100, 2);

        return (float) min($discount, $subtotal); // never exceed the subtotal
    }

    public function codDeliveryText(): string
    {
        return ($this->cod_delivery_days ?: '৫–৭') . ' দিন';
    }

    public function instantDeliveryText(): string
    {
        return ($this->instant_delivery_days ?: '২–৩') . ' দিন';
    }

    public function enabledMethods(): array
    {
        $methods = [];
        if ($this->cash_on_delivery_enabled) $methods[] = 'cash_on_delivery';
        if ($this->bkash_enabled)            $methods[] = 'bkash';
        if ($this->rocket_enabled)           $methods[] = 'rocket';
        if ($this->nagad_enabled)            $methods[] = 'nagad';

        return $methods;
    }

    public function numberFor(string $method): ?string
    {
        return match ($method) {
            'bkash'  => $this->bkash_number,
            'rocket' => $this->rocket_number,
            'nagad'  => $this->nagad_number,
            default  => null,
        };
    }
}
