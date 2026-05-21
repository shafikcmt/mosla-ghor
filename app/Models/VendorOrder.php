<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Courier;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorOrder extends Model
{
    protected $fillable = [
        'order_id', 'vendor_id', 'subtotal',
        'commission_type', 'commission_value_snapshot', 'commission_amount',
        'payable_amount', 'status',
        'fulfillment_status', 'courier_id', 'courier_name',
        'tracking_number', 'vendor_note', 'ready_at', 'handed_to_courier_at',
    ];

    protected $casts = [
        'subtotal'                  => 'decimal:2',
        'commission_value_snapshot' => 'decimal:2',
        'commission_amount'         => 'decimal:2',
        'payable_amount'            => 'decimal:2',
        'ready_at'                  => 'datetime',
        'handed_to_courier_at'      => 'datetime',
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

    public function courier(): BelongsTo
    {
        return $this->belongsTo(Courier::class);
    }

    public static function fulfillmentStatuses(): array
    {
        return [
            'pending'            => 'অপেক্ষায়',
            'processing'         => 'প্রসেসিং',
            'packed'             => 'প্যাক হয়েছে',
            'ready_for_pickup'   => 'পিকআপের জন্য প্রস্তুত',
            'handed_to_courier'  => 'কুরিয়ারে দেওয়া হয়েছে',
            'cancelled_by_vendor'=> 'ভেন্ডর কর্তৃক বাতিল',
        ];
    }
}
