<?php

namespace App\Services;

use App\Contracts\CourierDriverInterface;
use App\Models\Courier;

/**
 * Generic manual courier driver: no API. "Creating a parcel" just records the
 * booking; tracking (if any) is entered by admin/vendor later. Used as the
 * fallback for any courier without a usable API, and as the base for couriers
 * whose API isn't implemented yet (Pathao, Sundarban).
 */
class ManualCourierService implements CourierDriverInterface
{
    public function supportsApi(): bool
    {
        return false;
    }

    public function createParcel(Courier $courier, array $payload): array
    {
        return [
            'success'        => true,
            'manual'         => true,
            'tracking_id'    => $payload['tracking_id'] ?? null,
            'consignment_id' => null,
            'message'        => $courier->name . ' — ম্যানুয়াল বুকিং হিসেবে চিহ্নিত হয়েছে।',
        ];
    }

    public function testConnection(Courier $courier): array
    {
        return [
            'success' => false,
            'level'   => 'warning',
            'message' => $courier->name . ' একটি ম্যানুয়াল কুরিয়ার — API টেস্ট প্রযোজ্য নয়।',
        ];
    }
}
