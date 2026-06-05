<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Courier;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\WebsiteSetting;
use App\Services\CourierService;
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
        $order->load(['items', 'selectedCourier', 'suggestedCourier', 'zone', 'vendorOrders.vendor', 'vendorOrders.courier', 'vendorOrders.pickupPoint', 'vendorOrders.vendor.pickupPoints']);
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

    public function invoice(Order $order)
    {
        $order->load(['items', 'selectedCourier']);
        $siteName = WebsiteSetting::get('site_name', 'মসলা ঘর');
        $sitePhone = WebsiteSetting::get('phone', '');

        return view('admin.orders.invoice', compact('order', 'siteName', 'sitePhone'));
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

        // Courier can be set or cleared.
        $order->selected_courier_id = $data['selected_courier_id'] ?? null;

        // Status only changes when a value is chosen ("— পরিবর্তন না করুন —" sends blank).
        if (! empty($data['courier_status'])) {
            $order->courier_status = $data['courier_status'];
        }

        // Text fields are editable/clearable directly.
        $order->tracking_id    = $data['tracking_id'] ?? null;
        $order->consignment_id = $data['consignment_id'] ?? null;
        $order->courier_note   = $data['courier_note'] ?? null;

        // Manual delivery-charge override (blank field = keep auto/calculated).
        if (isset($data['delivery_charge']) && $data['delivery_charge'] !== null && $data['delivery_charge'] !== '') {
            $order->delivery_charge            = $data['delivery_charge'];
            $order->delivery_charge_overridden = true;
            $order->grand_total                = $order->subtotal + $order->packaging_cost + (float) $data['delivery_charge'];
        }

        // Manual courier-cost override (blank field = keep auto/calculated).
        if (isset($data['courier_cost']) && $data['courier_cost'] !== null && $data['courier_cost'] !== '') {
            $order->courier_cost            = $data['courier_cost'];
            $order->courier_cost_overridden = true;
        }

        // Zone change.
        if (! empty($data['delivery_zone_id']) && $data['delivery_zone_id'] != $order->delivery_zone_id) {
            $zone = DeliveryZone::find($data['delivery_zone_id']);
            if ($zone) {
                $order->delivery_zone_id   = $zone->id;
                $order->delivery_zone_name = $zone->zone_name;
                $order->delivery_zone_type = $zone->zone_type;
                $order->zone_overridden    = true;
            }
        }

        // Auto-calculate suggested courier + rate. Manual overrides are preserved.
        $suggestion = app(CourierService::class)->suggestForOrder($order);
        if ($suggestion) {
            $order->suggested_courier_id = $suggestion['courier_id'];

            if (! $order->delivery_charge_overridden) {
                $order->delivery_rate_id = $suggestion['delivery_rate_id'];
                $order->delivery_charge  = $suggestion['customer_delivery_charge'];
                $order->cod_charge       = $suggestion['cod_charge'];
                $order->grand_total      = $order->subtotal + $order->packaging_cost + (float) $order->delivery_charge;
            }

            if (! $order->courier_cost_overridden) {
                $order->courier_cost = $suggestion['courier_cost'];
            }
        }

        $order->save();

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'কুরিয়ার তথ্য আপডেট হয়েছে।');
    }

    /**
     * Clear manual overrides so charges fall back to auto-calculated rates.
     */
    public function recalculateCourier(Order $order)
    {
        $order->loadMissing('items');
        $order->delivery_charge_overridden = false;
        $order->courier_cost_overridden    = false;

        $suggestion = app(CourierService::class)->suggestForOrder($order);
        if (! $suggestion) {
            $order->save();

            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'অটো হিসাব করা যায়নি — এই জোন/ওজনের জন্য কোনো সক্রিয় ডেলিভারি রেট পাওয়া যায়নি।');
        }

        $order->suggested_courier_id = $suggestion['courier_id'];
        $order->delivery_rate_id     = $suggestion['delivery_rate_id'];
        $order->delivery_charge      = $suggestion['customer_delivery_charge'];
        $order->cod_charge           = $suggestion['cod_charge'];
        $order->courier_cost         = $suggestion['courier_cost'];
        $order->grand_total          = $order->subtotal + $order->packaging_cost + (float) $order->delivery_charge;
        $order->save();

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'ডেলিভারি চার্জ ও কুরিয়ার খরচ অটো হিসাব করা হয়েছে।');
    }

    public function sendToCourier(Request $request, Order $order)
    {
        $order->loadMissing('items', 'selectedCourier');

        $warnings = app(CourierService::class)->readinessWarnings($order);
        if (! empty($warnings)) {
            return redirect()->route('admin.orders.show', $order)
                ->with('error', 'কুরিয়ারে পাঠানো যায়নি: ' . implode(' ', $warnings));
        }

        $result = app(CourierService::class)->send(
            $order,
            $request->input('tracking_id'),
            $request->boolean('resend')
        );

        return redirect()->route('admin.orders.show', $order)
            ->with($result['success'] ? 'success' : 'error', $result['message']);
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
