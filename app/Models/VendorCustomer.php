<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCustomer extends Model
{
    protected $fillable = [
        'vendor_id', 'user_id', 'name', 'phone', 'whatsapp', 'email',
        'address', 'district', 'area', 'customer_type',
        'due_balance', 'notes', 'status',
    ];

    protected $casts = [
        'due_balance' => 'decimal:2',
    ];

    public const CUSTOMER_TYPES = ['Regular', 'Shop', 'Restaurant', 'Dealer', 'Retailer', 'Other'];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Orders this vendor created on behalf of this local customer. */
    public function orders()
    {
        return $this->hasMany(Order::class, 'vendor_customer_id');
    }

    /** Best phone to message on WhatsApp. */
    public function whatsappNumber(): ?string
    {
        return $this->whatsapp ?: $this->phone;
    }
}
