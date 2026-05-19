<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    public function createOrder(Courier $courier, Order $order): array
    {
        if (! $courier->api_enabled || ! $courier->api_key || ! $courier->api_secret) {
            return ['success' => false, 'error' => 'Steadfast API credentials not configured or API not enabled.'];
        }

        $baseUrl = rtrim($courier->base_url ?: 'https://portal.steadfast.com.bd/api/v1', '/');

        $address = implode(', ', array_filter([
            $order->full_address,
            $order->union_name,
            $order->upazila_name,
            $order->district_name,
            $order->division_name,
        ]));

        $payload = [
            'invoice'            => $order->order_number,
            'recipient_name'     => $order->customer_name,
            'recipient_phone'    => $order->mobile_number,
            'recipient_address'  => $address,
            'cod_amount'         => $order->payment_method === 'cash_on_delivery'
                ? (float) $order->grand_total
                : 0,
            'note'               => $order->order_note ?? '',
        ];

        try {
            $response = Http::withHeaders([
                'Api-Key'      => $courier->api_key,
                'Secret-Key'   => $courier->api_secret,
                'Content-Type' => 'application/json',
            ])->post($baseUrl . '/create_order', $payload);

            Log::info('Steadfast API request', ['order' => $order->order_number, 'payload' => $payload]);
            Log::info('Steadfast API response', ['status' => $response->status(), 'body' => $response->body()]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success'         => true,
                    'tracking_id'     => $data['consignment']['tracking_code'] ?? null,
                    'consignment_id'  => (string) ($data['consignment']['id'] ?? ''),
                ];
            }

            return ['success' => false, 'error' => 'Steadfast API error: ' . $response->body()];
        } catch (\Exception $e) {
            Log::error('Steadfast API exception', ['order' => $order->order_number, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
