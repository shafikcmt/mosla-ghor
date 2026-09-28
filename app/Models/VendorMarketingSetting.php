<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A vendor's own Meta Pixel. `admin_blocked` is the admin override and is never vendor-editable. */
class VendorMarketingSetting extends Model
{
    protected $fillable = ['vendor_id', 'pixel_enabled', 'pixel_id', 'test_event_code'];

    protected $casts = [
        'pixel_enabled' => 'boolean',
        'admin_blocked' => 'boolean',
        'last_event_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
