<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\DeliveryRate;
use App\Models\DeliveryZone;
use App\Models\Order;

class CourierService
{
    public function __construct(private CourierDriverFactory $drivers)
    {
    }

    /**
     * Validate that an order is ready to be sent to a courier.
     *
     * @return array<int,string> list of human-readable problems (empty = ready)
     */
    public function readinessWarnings(Order $order): array
    {
        $warnings = [];

        if (! $order->mobile_number)        $warnings[] = 'কাস্টমারের ফোন নম্বর নেই।';
        if (! $order->full_address)         $warnings[] = 'সম্পূর্ণ ঠিকানা নেই।';
        if (! $order->selected_courier_id)  $warnings[] = 'কুরিয়ার নির্বাচিত নেই।';
        if ($order->items->isEmpty())       $warnings[] = 'অর্ডারে কোনো পণ্য নেই।';
        if (! $order->stock_deducted_at)    $warnings[] = 'স্টক এখনো কাটা হয়নি।';

        return $warnings;
    }

    /**
     * Send an order to its selected courier. Routes to API when the courier
     * supports it and is enabled; otherwise records a manual booking.
     *
     * @param  bool  $resend  allow re-booking even if already sent via API
     * @return array{success:bool, message:string, manual?:bool}
     */
    public function send(Order $order, ?string $manualTracking = null, bool $resend = false): array
    {
        $courier = $order->selectedCourier;

        if (! $courier) {
            return ['success' => false, 'message' => 'কুরিয়ার নির্বাচিত নেই।'];
        }

        if ($courier->status !== 'active') {
            return ['success' => false, 'message' => $courier->name . ' কুরিয়ারটি নিষ্ক্রিয়। সক্রিয় করুন অথবা অন্য কুরিয়ার নির্বাচন করুন।'];
        }

        // ── API courier path ────────────────────────────────────────────────
        if ($courier->apiUsable()) {
            // Prevent accidental duplicate booking.
            if (! $resend && $order->consignment_id) {
                return [
                    'success' => false,
                    'message' => 'এই অর্ডার ইতোমধ্যে কুরিয়ারে পাঠানো হয়েছে (Consignment: ' . $order->consignment_id . ')। আবার পাঠাতে "পুনরায় পাঠান" ব্যবহার করুন।',
                ];
            }

            $result = $this->drivers->for($courier)->createParcel($courier, $this->payloadForOrder($order));

            if ($result['success']) {
                $order->update([
                    'tracking_id'        => $result['tracking_id'] ?? $order->tracking_id,
                    'consignment_id'     => $result['consignment_id'] ?? $order->consignment_id,
                    'courier_status'     => 'sent_to_courier',
                    'sent_to_courier_at' => now(),
                    'order_status'       => 'shipped',
                ]);

                return [
                    'success' => true,
                    'message' => $courier->name . '-এ সফলভাবে পাঠানো হয়েছে।' . (! empty($result['tracking_id']) ? ' Tracking: ' . $result['tracking_id'] : ''),
                ];
            }

            return ['success' => false, 'message' => $result['error'] ?? ($courier->name . ' API ত্রুটি।')];
        }

        // ── Manual courier path (via the manual driver) ─────────────────────
        $tracking = $manualTracking ?: $order->tracking_id;

        $this->drivers->manual()->createParcel($courier, ['tracking_id' => $tracking]);

        $order->update([
            'courier_status'     => 'sent_to_courier',
            'sent_to_courier_at' => now(),
            'tracking_id'        => $tracking,
            'order_status'       => 'shipped',
        ]);

        $note = $courier->supportsApi() && ! $courier->api_enabled
            ? ' (API বন্ধ — ম্যানুয়াল বুকিং হিসেবে চিহ্নিত)'
            : ' (ম্যানুয়াল বুকিং)';

        return [
            'success' => true,
            'manual'  => true,
            'message' => $order->order_number . ' — ' . $courier->name . ' কুরিয়ারে পাঠানো হিসেবে চিহ্নিত হয়েছে।' . $note,
        ];
    }

    /**
     * Build the standard parcel payload for a retail order.
     */
    private function payloadForOrder(Order $order): array
    {
        $address = implode(', ', array_filter([
            $order->full_address,
            $order->union_name,
            $order->upazila_name,
            $order->district_name,
            $order->division_name,
        ]));

        return [
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->customer_name,
            'recipient_phone'   => $order->mobile_number,
            'recipient_address' => $address,
            'cod_amount'        => $order->payment_method === 'cash_on_delivery'
                ? (float) $order->grand_total
                : 0,
            'note'              => $order->order_note ?? '',
        ];
    }

    /**
     * Compute the best/suggested courier + rate for an order based on its zone
     * and weight. Returns null when no zone/rate data is available.
     *
     * @return array{courier_id:int, delivery_rate_id:int, courier_cost:float, customer_delivery_charge:float, cod_charge:float}|null
     */
    public function suggestForOrder(Order $order): ?array
    {
        if (! $order->delivery_zone_id) {
            return null;
        }

        $zone = DeliveryZone::find($order->delivery_zone_id);
        if (! $zone) {
            return null;
        }

        $weight = (int) ($order->weight_gram ?: 1000);

        // Best rate among active rates for this zone/weight, cheapest customer charge wins.
        $rate = DeliveryRate::findBestRate($zone, $weight);
        if (! $rate || ! $rate->courier) {
            return null;
        }

        if ($rate->courier->status !== 'active') {
            return null;
        }

        $codCharge = 0.0;
        if ($order->payment_method === 'cash_on_delivery' && $rate->cod_percentage > 0) {
            $codCharge = round((float) $order->grand_total * ((float) $rate->cod_percentage / 100), 2);
        }

        return [
            'courier_id'               => $rate->courier_id,
            'delivery_rate_id'         => $rate->id,
            'courier_cost'             => (float) $rate->courier_cost,
            'customer_delivery_charge' => (float) $rate->customer_delivery_charge,
            'cod_charge'               => $codCharge,
        ];
    }
}
