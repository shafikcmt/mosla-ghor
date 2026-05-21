<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    protected $fillable = [
        'order_id', 'vendor_id', 'subtotal',
        'commission_type', 'commission_value_snapshot', 'commission_amount',
        'payable_amount', 'status',
    ];

    protected $casts = [
        'subtotal'                  => 'decimal:2',
        'commission_value_snapshot' => 'decimal:2',
        'commission_amount'         => 'decimal:2',
        'payable_amount'            => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'order_id')
            ->where('vendor_id', $this->vendor_id);
    }
}
