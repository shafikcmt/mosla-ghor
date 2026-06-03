<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\CourierSetting;
use App\Models\VendorOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    private function vendor()
    {
        return Auth::user()->vendor;
    }

    public function index(Request $request)
    {
        $vendor = $this->vendor();

        $query = $vendor->vendorOrders()->with('order')->latest();

        if ($request->filled('status')) {
            $query->where('fulfillment_status', $request->status);
        }

        $vendorOrders = $query->paginate(20);

        $fulfillmentStatuses = VendorOrder::fulfillmentStatuses();

        return view('vendor.orders.index', compact('vendor', 'vendorOrders', 'fulfillmentStatuses'));
    }

    public function show(VendorOrder $vendorOrder)
    {
        if ($vendorOrder->vendor_id !== $this->vendor()?->id) {
            abort(403);
        }

        $vendorOrder->load([
            'order',
            'order.items' => fn($q) => $q->where('vendor_id', $this->vendor()->id),
            'courier',
        ]);

        // Couriers shown to vendors only when admin allows selection. Note the
        // Courier model hides api_key/api_secret, so credentials are never exposed.
        $settings = CourierSetting::current();
        $couriers = $settings->vendorCanSelectCourier()
            ? Courier::active()->orderBy('name')->get()
            : collect();

        $fulfillmentStatuses = VendorOrder::fulfillmentStatuses();

        return view('vendor.orders.show', compact('vendorOrder', 'couriers', 'fulfillmentStatuses', 'settings'));
    }

    public function updateFulfillment(Request $request, VendorOrder $vendorOrder)
    {
        if ($vendorOrder->vendor_id !== $this->vendor()?->id) {
            abort(403);
        }

        $settings = CourierSetting::current();

        $data = $request->validate([
            'fulfillment_status' => 'required|in:pending,processing,packed,ready_for_pickup,handed_to_courier,cancelled_by_vendor',
            'courier_id'         => 'nullable|exists:couriers,id',
            'tracking_number'    => 'nullable|string|max:100',
            'vendor_note'        => 'nullable|string|max:500',
        ]);

        // Guard: vendor may not mark handed-to-courier unless allowed.
        if ($data['fulfillment_status'] === 'handed_to_courier'
            && ! $settings->vendor_can_mark_handover
            && $vendorOrder->fulfillment_status !== 'handed_to_courier') {
            return redirect()->route('vendor.orders.show', $vendorOrder)
                ->with('error', 'কুরিয়ারে হ্যান্ডওভার চিহ্নিত করার অনুমতি নেই। অ্যাডমিনের সাথে যোগাযোগ করুন।');
        }

        $updates = [
            'fulfillment_status' => $data['fulfillment_status'],
            'vendor_note'        => $data['vendor_note'] ?? null,
        ];

        // Tracking number — only if vendor is allowed to update it.
        if ($settings->vendor_can_update_tracking) {
            $updates['tracking_number'] = $data['tracking_number'] ?? null;
        }

        // Courier selection — only if admin allows vendor courier selection.
        if ($settings->vendorCanSelectCourier()) {
            if (! empty($data['courier_id'])) {
                // Only allow active couriers.
                $courier = Courier::active()->find($data['courier_id']);
                $updates['courier_id']   = $courier?->id;
                $updates['courier_name'] = $courier?->name;
            } else {
                $updates['courier_id']   = null;
                $updates['courier_name'] = null;
            }
        }

        // Timestamps for status transitions
        if ($data['fulfillment_status'] === 'ready_for_pickup' && ! $vendorOrder->ready_at) {
            $updates['ready_at'] = now();
        }
        if ($data['fulfillment_status'] === 'handed_to_courier' && ! $vendorOrder->handed_to_courier_at) {
            $updates['handed_to_courier_at'] = now();
        }

        $vendorOrder->update($updates);

        return redirect()->route('vendor.orders.show', $vendorOrder)
            ->with('success', 'ফুলফিলমেন্ট স্ট্যাটাস আপডেট হয়েছে।');
    }
}
