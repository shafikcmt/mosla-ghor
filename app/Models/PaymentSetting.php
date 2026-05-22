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
