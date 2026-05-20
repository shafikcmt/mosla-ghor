<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceSetting extends Model
{
    protected $fillable = [
        'markup_25g',
        'markup_50g',
        'markup_100g',
        'markup_250g',
        'markup_500g',
        'markup_1000g',
        'rounding_type',
        'default_packaging_cost',
        'minimum_order_amount',
        'currency_symbol',
    ];

    protected $casts = [
        'markup_25g'             => 'decimal:2',
        'markup_50g'             => 'decimal:2',
        'markup_100g'            => 'decimal:2',
        'markup_250g'            => 'decimal:2',
        'markup_500g'            => 'decimal:2',
        'markup_1000g'           => 'decimal:2',
        'default_packaging_cost' => 'decimal:2',
        'minimum_order_amount'   => 'decimal:2',
    ];

    // Always load the single settings row via this method
   public static function current(): static
    {
        return static::firstOrNew([], [
            'markup_25g' => 0,
            'markup_50g' => 0,
            'markup_100g' => 0,
            'markup_250g' => 0,
            'markup_500g' => 0,
            'markup_1000g' => 0,
            'default_packaging_cost' => 0,
            'minimum_order_amount' => 0,
            'rounding_type' => 'none',
            'currency_symbol' => '৳',
        ]);
    }

    // Return the markup % for a given pack weight in grams
    public function markupFor(int $grams): float
    {
        return (float) match (true) {
            $grams <= 25  => $this->markup_25g,
            $grams <= 50  => $this->markup_50g,
            $grams <= 100 => $this->markup_100g,
            $grams <= 250 => $this->markup_250g,
            $grams <= 500 => $this->markup_500g,
            default       => $this->markup_1000g,
        };
    }

    // Round a price according to the configured rounding_type
    public function roundPrice(float $price): float
    {
        return match ($this->rounding_type) {
            'nearest_5'  => (float) (round($price / 5) * 5),
            'nearest_10' => (float) (round($price / 10) * 10),
            default      => round($price, 2),
        };
    }

    // Format a price with the configured currency symbol
    public function format(float $price): string
    {
        return $this->currency_symbol . number_format($price, 0);
    }
}
