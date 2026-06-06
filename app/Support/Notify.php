<?php

namespace App\Support;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Notifications\Notification;

/**
 * Thin fan-out helpers for database notifications. Every send is wrapped so a
 * notification failure can never break the surrounding business action.
 */
class Notify
{
    /** Notify every admin user. */
    public static function admins(Notification $notification): void
    {
        try {
            User::where('is_admin', true)->get()->each->notify($notification);
        } catch (\Throwable) {
            // non-critical
        }
    }

    /** Notify the user account behind a vendor (if any). */
    public static function vendor(?Vendor $vendor, Notification $notification): void
    {
        try {
            $vendor?->user?->notify($notification);
        } catch (\Throwable) {
            // non-critical
        }
    }
}
