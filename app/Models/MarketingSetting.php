<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Platform Meta Pixel + Conversions API settings (single row). The CAPI token is encrypted at rest and never serialised. */
class MarketingSetting extends Model
{
    protected $fillable = [
        'pixel_enabled', 'pixel_ids', 'test_event_code', 'platform_pixel_scope',
        'track_admin_users', 'vendor_pixels_enabled', 'vendor_capi_allowed',
        'capi_enabled', 'capi_access_token',
    ];

    protected $hidden = ['capi_access_token'];

    protected $casts = [
        'pixel_enabled'         => 'boolean',
        'pixel_ids'             => 'array',
        'track_admin_users'     => 'boolean',
        'vendor_pixels_enabled' => 'boolean',
        'vendor_capi_allowed'   => 'boolean',
        'capi_enabled'          => 'boolean',
        'capi_access_token'     => 'encrypted',
        'capi_last_success_at'  => 'datetime',
        'capi_last_error_at'    => 'datetime',
    ];

    /** firstOrCreate (never firstOrNew): an unsaved model makes update() silently match nothing. */
    public static function current(): static
    {
        return static::firstOrCreate([], [
            'pixel_enabled'         => false,
            'pixel_ids'             => [],
            'platform_pixel_scope'  => 'all',
            'track_admin_users'     => false,
            'vendor_pixels_enabled' => false,
            'vendor_capi_allowed'   => false,
        ]);
    }
}
