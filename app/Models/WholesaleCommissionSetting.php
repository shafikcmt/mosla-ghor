<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WholesaleCommissionSetting extends Model
{
    protected $fillable = [
        'scope', 'scope_id', 'applies_to',
        'commission_type', 'commission_value',
        'is_active', 'note',
    ];

    protected $casts = [
        'commission_value' => 'decimal:2',
        'is_active'        => 'boolean',
    ];

    // Resolve commission setting for a given vendor (vendor-specific → global fallback)
    public static function resolveFor(int $vendorId, string $appliesTo = 'wholesale'): self
    {
        // Vendor-specific setting takes priority
        $vendor = static::where('scope', 'vendor')
            ->where('scope_id', $vendorId)
            ->where('applies_to', $appliesTo)
            ->where('is_active', true)
            ->first();

        if ($vendor) {
            return $vendor;
        }

        // Global wholesale setting
        $global = static::where('scope', 'global')
            ->where('applies_to', $appliesTo)
            ->where('is_active', true)
            ->first();

        if ($global) {
            return $global;
        }

        // No setting found — return zero-commission default
        $default = new self();
        $default->commission_type  = 'percentage';
        $default->commission_value = 0;
        return $default;
    }

    public function calculate(float $subtotal): float
    {
        if ($this->commission_type === 'percentage') {
            return round($subtotal * (float) $this->commission_value / 100, 2);
        }
        return round((float) $this->commission_value, 2);
    }
}
