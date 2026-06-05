<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CourierSetting extends Model
{
    protected $fillable = [
        'vendor_can_select_courier',
        'vendor_can_update_tracking',
        'vendor_can_mark_handover',
        'vendor_can_setup_pickup_address',
        'vendor_can_create_parcel',
        'courier_selection_mode',
        'vendor_courier_mode',
    ];

    protected $casts = [
        'vendor_can_select_courier'       => 'boolean',
        'vendor_can_update_tracking'      => 'boolean',
        'vendor_can_mark_handover'        => 'boolean',
        'vendor_can_setup_pickup_address' => 'boolean',
        'vendor_can_create_parcel'        => 'boolean',
    ];

    /**
     * Legacy selection mode — kept for back-compat. All logic now reads
     * vendor_courier_mode (see below); this is updated in lock-step.
     */
    public const SELECTION_MODES = [
        'admin_only'     => 'শুধু অ্যাডমিন',
        'vendor_suggest' => 'ভেন্ডর সাজেস্ট করতে পারবে',
        'vendor_select'  => 'ভেন্ডর সিলেক্ট করতে পারবে',
    ];

    /**
     * Unified vendor courier mode:
     * - admin_only:         vendor cannot parcel; admin handles courier.
     * - vendor_can_request: vendor chooses courier + requests parcel; admin approves/sends.
     * - vendor_can_parcel:  vendor creates parcel via admin API settings (no credentials shown).
     */
    public const VENDOR_COURIER_MODES = [
        'admin_only'         => 'শুধু অ্যাডমিন',
        'vendor_can_request' => 'ভেন্ডর রিকোয়েস্ট করতে পারবে',
        'vendor_can_parcel'  => 'ভেন্ডর নিজে পার্সেল করতে পারবে',
    ];

    /** Map the new mode back to the legacy column so both stay consistent. */
    public const MODE_TO_LEGACY = [
        'admin_only'         => 'admin_only',
        'vendor_can_request' => 'vendor_suggest',
        'vendor_can_parcel'  => 'vendor_select',
    ];

    /**
     * Single-row settings. firstOrCreate (not firstOrNew) so update() persists reliably.
     */
    public static function current(): self
    {
        return static::firstOrCreate([], [
            'vendor_can_select_courier'       => true,
            'vendor_can_update_tracking'      => true,
            'vendor_can_mark_handover'        => true,
            'vendor_can_setup_pickup_address' => true,
            'vendor_can_create_parcel'        => false,
            'courier_selection_mode'          => 'admin_only',
            'vendor_courier_mode'             => 'admin_only',
        ]);
    }

    public function mode(): string
    {
        return $this->vendor_courier_mode ?: 'admin_only';
    }

    /** Vendor may choose a courier (request or full-parcel modes). */
    public function vendorCanSelectCourier(): bool
    {
        return $this->vendor_can_select_courier
            && in_array($this->mode(), ['vendor_can_request', 'vendor_can_parcel'], true);
    }

    /** Vendor may request a parcel for admin to approve/send. */
    public function vendorCanRequestParcel(): bool
    {
        return in_array($this->mode(), ['vendor_can_request', 'vendor_can_parcel'], true);
    }

    /** Vendor may create the parcel themselves using admin-saved API credentials. */
    public function vendorCanCreateParcel(): bool
    {
        return $this->mode() === 'vendor_can_parcel' && (bool) $this->vendor_can_create_parcel;
    }

    /** Vendor may manage their own pickup addresses. */
    public function vendorCanSetupPickup(): bool
    {
        return (bool) $this->vendor_can_setup_pickup_address;
    }
}
