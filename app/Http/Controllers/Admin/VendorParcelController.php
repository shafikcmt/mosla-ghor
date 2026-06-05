<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\VendorOrder;
use App\Models\VendorPickupPoint;
use App\Services\CourierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin control over a vendor order's courier parcel: create/re-send on behalf
 * of the vendor, or directly edit courier/pickup/tracking/status.
 */
class VendorParcelController extends Controller
{
    /**
     * Create (or re-send) a parcel for a vendor order, on behalf of the vendor.
     * Admin may use any active courier — not limited to vendor-allowed ones.
     */
    public function store(Request $request, VendorOrder $vendorOrder, CourierService $courierService)
    {
        $data = $request->validate([
            'courier_id'      => 'required|exists:couriers,id',
            'pickup_point_id' => 'nullable|exists:vendor_pickup_points,id',
            'parcel_note'     => 'nullable|string|max:500',
            'resend'          => 'nullable|boolean',
        ], [
            'courier_id.required' => 'কুরিয়ার নির্বাচন করুন।',
        ]);

        $courier = Courier::active()->find($data['courier_id']);
        if (! $courier) {
            return $this->backToOrder($vendorOrder, 'error', 'নির্বাচিত কুরিয়ার সক্রিয় নয়।');
        }

        $pickup = ! empty($data['pickup_point_id'])
            ? VendorPickupPoint::where('vendor_id', $vendorOrder->vendor_id)->find($data['pickup_point_id'])
            : null;

        $result = $courierService->createVendorParcel(
            $vendorOrder,
            $courier,
            $pickup,
            $data['parcel_note'] ?? null,
            'admin',
            (int) Auth::id(),
            $request->boolean('resend'),
        );

        return $this->backToOrder($vendorOrder, $result['success'] ? 'success' : 'error', $result['message']);
    }

    /**
     * Directly edit a vendor order's courier/pickup/tracking/status (no API call).
     */
    public function update(Request $request, VendorOrder $vendorOrder)
    {
        $data = $request->validate([
            'courier_id'      => 'nullable|exists:couriers,id',
            'pickup_point_id' => 'nullable|exists:vendor_pickup_points,id',
            'tracking_number' => 'nullable|string|max:100',
            'consignment_id'  => 'nullable|string|max:100',
            'courier_status'  => 'nullable|string|max:50',
            'courier_note'    => 'nullable|string|max:500',
        ]);

        $updates = [
            'tracking_number' => $data['tracking_number'] ?? null,
            'consignment_id'  => $data['consignment_id'] ?? null,
            'courier_note'    => $data['courier_note'] ?? null,
        ];

        if (! empty($data['courier_status'])) {
            $updates['courier_status'] = $data['courier_status'];
        }

        if (! empty($data['courier_id'])) {
            $courier = Courier::find($data['courier_id']);
            $updates['courier_id']   = $courier?->id;
            $updates['courier_name'] = $courier?->name;
        }

        // Pickup point must belong to this vendor.
        if (! empty($data['pickup_point_id'])) {
            $pickup = VendorPickupPoint::where('vendor_id', $vendorOrder->vendor_id)->find($data['pickup_point_id']);
            $updates['pickup_point_id'] = $pickup?->id;
        }

        $vendorOrder->update($updates);

        return $this->backToOrder($vendorOrder, 'success', 'ভেন্ডর পার্সেল তথ্য আপডেট হয়েছে।');
    }

    private function backToOrder(VendorOrder $vendorOrder, string $key, string $message)
    {
        return redirect()->route('admin.orders.show', $vendorOrder->order_id)->with($key, $message);
    }
}
