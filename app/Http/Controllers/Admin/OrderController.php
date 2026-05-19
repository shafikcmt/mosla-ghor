<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Services\SteadfastService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('selectedCourier')->latest()->paginate(25);
        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load(['items', 'selectedCourier', 'suggestedCourier', 'zone']);
        $couriers = Courier::orderBy('name')->get();
        $zones    = DeliveryZone::where('is_active', true)->orderBy('zone_name')->get();

        $courierStatuses = [
            'pending'           => 'অপেক্ষায়',
            'processing'        => 'প্রসেসিং',
            'ready_for_courier' => 'কুরিয়ার প্রস্তুত',
            'sent_to_courier'   => 'কুরিয়ারে পাঠানো',
            'picked_up'         => 'পিক-আপ হয়েছে',
            'in_transit'        => 'ট্রানজিটে',
            'delivered'         => 'ডেলিভারড',
            'returned'          => 'ফেরত এসেছে',
            'cancelled'         => 'বাতিল',
            'failed_delivery'   => 'ডেলিভারি ব্যর্থ',
        ];

        return view('admin.orders.show', compact('order', 'couriers', 'zones', 'courierStatuses'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'payment_status' => 'required|in:pending,verified,failed',
            'order_status'   => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled',
        ]);

        $needsStockRestore = $request->order_status === 'cancelled'
            && $order->stock_deducted_at
            && ! $order->stock_restored_at;

        $order->update([
            'payment_status' => $request->payment_status,
            'order_status'   => $request->order_status,
        ]);

        if ($needsStockRestore) {
            $this->deductedStockRestore($order);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'অর্ডার স্ট্যাটাস আপডেট হয়েছে।');
    }

    public function updateCourier(Request $request, Order $order)
    {
        $data = $request->validate([
            'selected_courier_id'  => 'nullable|exists:couriers,id',
            'delivery_zone_id'     => 'nullable|exists:delivery_zones,id',
            'delivery_charge'      => 'nullable|numeric|min:0',
            'courier_cost'         => 'nullable|numeric|min:0',
            'tracking_id'          => 'nullable|string|max:100',
            'consignment_id'       => 'nullable|string|max:100',
            'courier_status'       => 'nullable|string|max:50',
            'courier_note'         => 'nullable|string|max:1000',
        ]);

        $updates = array_filter([
            'selected_courier_id' => $data['selected_courier_id'] ?? null,
            'courier_status'      => $data['courier_status'] ?? null,
            'tracking_id'         => $data['tracking_id'] ?? null,
            'consignment_id'      => $data['consignment_id'] ?? null,
            'courier_note'        => $data['courier_note'] ?? null,
        ], fn($v) => $v !== null);

        if (isset($data['delivery_charge']) && $data['delivery_charge'] !== null) {
            $updates['delivery_charge']            = $data['delivery_charge'];
            $updates['delivery_charge_overridden'] = true;
            $updates['grand_total']                = $order->subtotal + $order->packaging_cost + $data['delivery_charge'];
        }

        if (isset($data['courier_cost']) && $data['courier_cost'] !== null) {
            $updates['courier_cost']             = $data['courier_cost'];
            $updates['courier_cost_overridden']  = true;
        }

        if (! empty($data['delivery_zone_id']) && $data['delivery_zone_id'] != $order->delivery_zone_id) {
            $zone = DeliveryZone::find($data['delivery_zone_id']);
            if ($zone) {
                $updates['delivery_zone_id']   = $zone->id;
                $updates['delivery_zone_name'] = $zone->zone_name;
                $updates['delivery_zone_type'] = $zone->zone_type;
                $updates['zone_overridden']    = true;
            }
        }

        $order->update($updates);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'কুরিয়ার তথ্য আপডেট হয়েছে।');
    }

    public function sendToCourier(Request $request, Order $order)
    {
        // Validation before sending
        $warnings = [];

        if (! $order->mobile_number)      $warnings[] = 'কাস্টমারের ফোন নম্বর নেই।';
        if (! $order->full_address)       $warnings[] = 'সম্পূর্ণ ঠিকানা নেই।';
        if (! $order->delivery_zone_id)   $warnings[] = 'ডেলিভারি জোন নির্বাচিত নেই।';
        if (! $order->selected_courier_id) $warnings[] = 'কুরিয়ার নির্বাচিত নেই।';
        if ($order->items->isEmpty())     $warnings[] = 'অর্ডারে কোনো পণ্য নেই।';
        if (! $order->stock_deducted_at)  $warnings[] = 'স্টক এখনো কাটা হয়নি।';

        if (! empty($warnings)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'কুরিয়ারে পাঠানো যায়নি: ' . implode(' ', $warnings));
        }

        $courier = $order->selectedCourier;

        // Steadfast API send
        if ($courier->slug === 'steadfast' && $courier->api_enabled) {
            $result = app(SteadfastService::class)->createOrder($courier, $order);

            if ($result['success']) {
                $order->update([
                    'tracking_id'        => $result['tracking_id'],
                    'consignment_id'     => $result['consignment_id'],
                    'courier_status'     => 'sent_to_courier',
                    'sent_to_courier_at' => now(),
                    'order_status'       => 'shipped',
                ]);

                return redirect()->route('admin.orders.show', $order)
                    ->with('success', 'Steadfast-এ সফলভাবে পাঠানো হয়েছে। Tracking: ' . $result['tracking_id']);
            }

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'Steadfast API ত্রুটি: ' . $result['error']);
        }

        // Manual / non-API couriers — mark as sent with manual tracking
        $tracking = $request->input('tracking_id') ?: $order->tracking_id;
        $order->update([
            'courier_status'     => 'sent_to_courier',
            'sent_to_courier_at' => now(),
            'tracking_id'        => $tracking,
            'order_status'       => 'shipped',
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'অর্ডার কুরিয়ারে পাঠানো হিসেবে চিহ্নিত হয়েছে।');
    }

    public function markDelivered(Order $order)
    {
        $order->update([
            'courier_status' => 'delivered',
            'order_status'   => 'delivered',
            'delivered_at'   => now(),
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'অর্ডার ডেলিভারড চিহ্নিত হয়েছে।');
    }

    public function markReturned(Order $order)
    {
        $order->update([
            'courier_status' => 'returned',
            'returned_at'    => now(),
        ]);

        // Auto-restore stock when an order is returned
        if ($order->stock_deducted_at && ! $order->stock_restored_at) {
            $this->deductedStockRestore($order);
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'অর্ডার ফেরত চিহ্নিত হয়েছে। স্টক পুনরুদ্ধার হয়েছে।');
    }

    public function restoreStock(Order $order)
    {
        if (! $order->stock_deducted_at) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'এই অর্ডারের স্টক কখনো কাটা হয়নি।');
        }

        if ($order->stock_restored_at) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'স্টক ইতোমধ্যেই পুনরুদ্ধার হয়েছে।');
        }

        $this->deductedStockRestore($order);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'স্টক সফলভাবে পুনরুদ্ধার হয়েছে।');
    }

    private function deductedStockRestore(Order $order): void
    {
        $order->loadMissing('items');

        $neededByProduct = [];
        foreach ($order->items as $item) {
            $pid = $item->product_id;
            $neededByProduct[$pid] = ($neededByProduct[$pid] ?? 0) + $item->quantity_gram;
        }

        foreach ($neededByProduct as $productId => $totalGram) {
            Product::where('id', $productId)
                ->increment('stock', (int) ceil($totalGram / 1000));
        }

        $order->update(['stock_restored_at' => now()]);
    }
}
