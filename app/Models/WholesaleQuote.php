<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WholesaleQuote extends Model
{
    protected $fillable = [
        'enquiry_id', 'vendor_id', 'customer_id',
        'unit_price', 'quantity', 'quantity_unit', 'subtotal',
        'delivery_charge', 'advance_required', 'payment_options',
        'note', 'valid_until', 'status',
        'admin_approved', 'admin_approved_at', 'admin_approved_by',
        'admin_rejected_at', 'admin_note', 'order_id',
    ];

    protected $casts = [
        'unit_price'          => 'decimal:2',
        'quantity'            => 'decimal:2',
        'subtotal'            => 'decimal:2',
        'delivery_charge'     => 'decimal:2',
        'advance_required'    => 'decimal:2',
        'payment_options'     => 'array',
        'valid_until'         => 'date',
        'admin_approved'      => 'boolean',
        'admin_approved_at'   => 'datetime',
        'admin_rejected_at'   => 'datetime',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(WholesaleEnquiry::class, 'enquiry_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_approved_by');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function grandTotal(): float
    {
        return (float) $this->subtotal + (float) $this->delivery_charge;
    }

    public static function statuses(): array
    {
        return [
            'pending'  => 'অনুমোদন অপেক্ষায়',
            'approved' => 'অনুমোদিত',
            'accepted' => 'গ্রহণ করা হয়েছে',
            'rejected' => 'প্রত্যাখ্যাত',
            'expired'  => 'মেয়াদ শেষ',
        ];
    }

    public function statusLabel(): string
    {
        return static::statuses()[$this->status] ?? $this->status;
    }

    public function isExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }
}
