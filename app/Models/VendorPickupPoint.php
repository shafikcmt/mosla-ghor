<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorPickupPoint extends Model
{
    protected $fillable = [
        'vendor_id', 'pickup_name', 'contact_person_name', 'phone', 'alternate_phone',
        'address', 'district', 'city', 'zone_area', 'postal_code', 'note',
        'is_default', 'status',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Make this the vendor's only default pickup point.
     */
    public function makeDefault(): void
    {
        static::where('vendor_id', $this->vendor_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        if (! $this->is_default) {
            $this->update(['is_default' => true]);
        }
    }

    /**
     * The vendor's default active pickup point, falling back to any active one.
     */
    public static function defaultFor(int $vendorId): ?self
    {
        return static::where('vendor_id', $vendorId)->where('status', 'active')
            ->orderByDesc('is_default')->orderBy('id')->first();
    }
}
