<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    protected $fillable = [
        'vendor_can_select_courier',
        'vendor_can_update_tracking',
        'vendor_can_mark_handover',
        'courier_selection_mode',
    ];

    protected $casts = [
        'vendor_can_select_courier'  => 'boolean',
        'vendor_can_update_tracking' => 'boolean',
        'vendor_can_mark_handover'   => 'boolean',
    ];

    public const SELECTION_MODES = [
        'admin_only'     => 'শুধু অ্যাডমিন',
        'vendor_suggest' => 'ভেন্ডর সাজেস্ট করতে পারবে',
        'vendor_select'  => 'ভেন্ডর সিলেক্ট করতে পারবে',
    ];

    /**
     * Single-row settings. firstOrCreate (not firstOrNew) so update() persists reliably.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'vendor_can_select_courier'  => true,
            'vendor_can_update_tracking' => true,
            'vendor_can_mark_handover'   => true,
            'courier_selection_mode'     => 'admin_only',
        ]);
    }

    public function vendorCanSelectCourier(): bool
    {
        // Selecting a courier is only meaningful when the mode allows vendor input.
        return $this->vendor_can_select_courier
            && in_array($this->courier_selection_mode, ['vendor_suggest', 'vendor_select'], true);
    }
}
