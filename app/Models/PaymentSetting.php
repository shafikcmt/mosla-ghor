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
    ];

    protected $casts = [
        'cash_on_delivery_enabled' => 'boolean',
        'bkash_enabled'            => 'boolean',
        'rocket_enabled'           => 'boolean',
        'nagad_enabled'            => 'boolean',
    ];

   public static function current(): static
    {
        return static::firstOrNew([], [
            'bkash_number' => '',
            'bkash_type' => '',
            'rocket_number' => '',
            'rocket_type' => '',
            'nagad_number' => '',
            'nagad_type' => '',
            'is_cash_on_delivery_active' => true,
        ]);
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
