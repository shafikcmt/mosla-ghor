<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPayout extends Model
{
    protected $fillable = [
        'vendor_id', 'amount', 'payment_method', 'payment_details',
        'status', 'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
