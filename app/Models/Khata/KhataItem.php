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

    public function stockValue(): float
    {
        return max(0, (float) $this->stock) * (float) $this->purchase_price;
    }

    public function isLow(): bool
    {
        return $this->low_stock_alert !== null && (float) $this->stock <= (float) $this->low_stock_alert;
    }
}
