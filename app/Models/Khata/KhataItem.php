<?php

namespace App\Models\Khata;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KhataItem extends Model
{
    protected $fillable = [
        'vendor_id', 'name', 'category', 'unit', 'sale_price', 'purchase_price',
        'stock', 'low_stock_alert', 'note', 'is_active',
    ];

    protected $casts = [
        'sale_price'      => 'decimal:2',
        'purchase_price'  => 'decimal:2',
        'stock'           => 'decimal:3',
        'low_stock_alert' => 'decimal:3',
        'is_active'       => 'boolean',
    ];

    public const UNITS = ['pcs' => 'পিস', 'kg' => 'কেজি', 'gram' => 'গ্রাম', 'litre' => 'লিটার', 'packet' => 'প্যাকেট', 'bag' => 'বস্তা', 'carton' => 'কার্টন', 'dozen' => 'ডজন', 'box' => 'বক্স'];

    public function lines(): HasMany
    {
        return $this->hasMany(KhataTransactionItem::class, 'item_id');
    }

    public function unitLabel(): string
    {
        return self::UNITS[$this->unit] ?? $this->unit;
    }

    /** "27 কেজি 700 গ্রাম" for kg, otherwise "12.5 পিস". */
    public static function formatQty(float|string|null $qty, ?string $unit): string
    {
        $qty  = round((float) $qty, 3);
        $sign = $qty < 0 ? '−' : '';
        $abs  = abs($qty);
        if ($unit === 'kg') {
            $kg = (int) floor($abs + 1e-9);
            $g  = (int) round(($abs - $kg) * 1000);
            if ($g === 1000) { $kg++; $g = 0; }
            if ($g > 0) {
                return $sign.($kg > 0 ? $kg.' কেজি ' : '').$g.' গ্রাম';
            }

            return $sign.$kg.' কেজি';
        }

        return $sign.rtrim(rtrim(number_format($abs, 3, '.', ''), '0'), '.').' '.(self::UNITS[$unit] ?? $unit);
    }

    public function stockLabel(): string
    {
        return self::formatQty($this->stock, $this->unit);
    }

    public function stockValue(): float
    {
        return max(0, (float) $this->stock) * (float) $this->purchase_price;
    }

    public function isLow(): bool
    {
        return $this->low_stock_alert !== null && (float) $this->stock <= (float) $this->low_stock_alert;
    }
}
