<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStockMovement extends Model
{
    protected $fillable = [
        'vendor_id', 'product_id', 'product_variant_id',
        'type', 'quantity', 'previous_stock', 'new_stock',
        'reference_type', 'reference_id', 'note', 'created_by',
    ];

    protected $casts = [
        'quantity'       => 'decimal:3',
        'previous_stock' => 'decimal:3',
        'new_stock'      => 'decimal:3',
    ];

    public const TYPES = [
        'add'        => 'স্টক যোগ',
        'reduce'     => 'স্টক কমানো',
        'adjustment' => 'সমন্বয়',
        'order'      => 'অর্ডার',
        'return'     => 'ফেরত',
        'cancel'     => 'বাতিল',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
