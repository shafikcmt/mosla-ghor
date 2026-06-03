<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SteadfastService
{
    private const DEFAULT_BASE_URL = 'https://portal.steadfast.com.bd/api/v1';

    /**
     * Create a delivery consignment at Steadfast for the given order.
     *
     * @return array{success:bool, tracking_id?:?string, consignment_id?:?string, error?:string}
     */
    public function createOrder(Courier $courier, Order $order): array
    {
        if (! $courier->api_enabled || ! $courier->api_key || ! $courier->api_secret) {
            return ['success' => false, 'error' => 'Steadfast API চালু নেই অথবা API Key/Secret কনফিগার করা হয়নি।'];
        }

        $baseUrl = $this->baseUrl($courier);

        $address = implode(', ', array_filter([
            $order->full_address,
            $order->union_name,
            $order->upazila_name,
            $order->district_name,
            $order->division_name,
        ]));

        $payload = [
            'invoice'           => $order->order_number,
            'recipient_name'    => $order->customer_name,
            'recipient_phone'   => $order->mobile_number,
            'recipient_address' => $address,
            'cod_amount'        => $order->payment_method === 'cash_on_delivery'
                ? (float) $order->grand_total
                : 0,
            'note'              => $order->order_note ?? '',
        ];

        try {
            $response = Http::withHeaders($this->headers($courier))
                ->timeout(30)
                ->acceptJson()
                ->post($baseUrl . '/create_order', $payload);

            // NOTE: never log api_key/api_secret — only the payload + sanitized response.
            Log::info('Steadfast create_order', [
                'courier' => $courier->name,
                'order'   => $order->order_number,
                'status'  => $response->status(),
                'body'    => $this->sanitize($response->body()),
            ]);

            if ($response->successful()) {
                $data        = $response->json();
                $consignment = $data['consignment'] ?? [];

                // Steadfast returns status=200 in body on success.
                if (($data['status'] ?? null) == 200 && ! empty($consignment)) {
                    $this->recordResult($courier, true);

                    return [
                        'success'        => true,
                        'tracking_id'    => $consignment['tracking_code'] ?? null,
                        'consignment_id' => (string) ($consignment['consignment_id'] ?? $consignment['id'] ?? ''),
                    ];
                }

                $message = $this->friendlyError($data, $response->body());
                $this->recordResult($courier, false, $message);

                return ['success' => false, 'error' => $message];
            }

            $message = $this->friendlyError($response->json(), $response->body(), $response->status());
            $this->recordResult($courier, false, $message);

            return ['success' => false, 'error' => $message];
        } catch (\Throwable $e) {
            Log::error('Steadfast create_order exception', [
                'courier' => $courier->name,
                'order'   => $order->order_number,
                'error'   => $e->getMessage(),
            ]);
            $this->recordResult($courier, false, $e->getMessage());

            return ['success' => false, 'error' => 'Steadfast সার্ভারে সংযোগ করা যায়নি: ' . $e->getMessage()];
        }
    }

    /**
     * Lightweight connectivity / credential check.
     * Uses the balance endpoint which is safe and read-only.
     *
     * @return array{success:bool, message:string}
     */
    public function testConnection(Courier $courier): array
    {
        if (! $courier->api_key || ! $courier->api_secret) {
            return ['success' => false, 'message' => 'API Key এবং Secret Key দিন তারপর টেস্ট করুন।'];
        }

        $baseUrl = $this->baseUrl($courier);

        try {
            $response = Http::withHeaders($this->headers($courier))
                ->timeout(20)
                ->acceptJson()
                ->get($baseUrl . '/get_balance');

            Log::info('Steadfast test connection', [
                'courier' => $courier->name,
                'status'  => $response->status(),
                'body'    => $this->sanitize($response->body()),
            ]);

            if ($response->successful() && ($response->json('status') == 200 || $response->json('current_balance') !== null)) {
                $balance = $response->json('current_balance');
                $this->recordResult($courier, true);

                return [
                    'success' => true,
                    'message' => 'সংযোগ সফল হয়েছে।' . ($balance !== null ? ' বর্তমান ব্যালেন্স: ৳' . number_format((float) $balance, 2) : ''),
                ];
            }

            if ($response->status() === 401) {
                $message = 'API Key অথবা Secret Key সঠিক নয় (Unauthorized)।';
            } else {
                $message = $this->friendlyError($response->json(), $response->body(), $response->status());
            }

            $this->recordResult($courier, false, $message);

            return ['success' => false, 'message' => $message];
        } catch (\Throwable $e) {
            Log::error('Steadfast test connection exception', [
                'courier' => $courier->name,
                'error'   => $e->getMessage(),
            ]);
            $this->recordResult($courier, false, $e->getMessage());

            return ['success' => false, 'message' => 'সংযোগ ব্যর্থ: ' . $e->getMessage()];
        }
    }

    private function baseUrl(Courier $courier): string
    {
        return rtrim($courier->base_url ?: self::DEFAULT_BASE_URL, '/');
    }

    private function headers(Courier $courier): array
    {
        return [
            'Api-Key'      => $courier->api_key,
            'Secret-Key'   => $courier->api_secret,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * Turn a Steadfast error payload into a readable single-line message.
     */
    private function friendlyError(?array $data, string $rawBody, ?int $status = null): string
    {
        $prefix = $status ? "Steadfast API ত্রুটি ($status): " : 'Steadfast API ত্রুটি: ';

        if (is_array($data)) {
            if (! empty($data['message']) && is_string($data['message'])) {
                $msg = $data['message'];

                // Laravel-style validation errors → flatten.
                if (! empty($data['errors']) && is_array($data['errors'])) {
                    $flat = [];
                    foreach ($data['errors'] as $field => $messages) {
                        $flat[] = is_array($messages) ? implode(', ', $messages) : (string) $messages;
                    }
                    if ($flat) {
                        $msg .= ' (' . implode('; ', $flat) . ')';
                    }
                }

                return $prefix . $msg;
            }
        }

        $body = trim($this->sanitize($rawBody));

        return $prefix . ($body !== '' ? mb_substr($body, 0, 300) : 'অজানা ত্রুটি।');
    }

    /**
     * Defensive: strip anything that looks like a credential from logged/echoed text.
     */
    private function sanitize(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        return preg_replace('/("?(?:api[_-]?key|secret[_-]?key)"?\s*[:=]\s*")([^"]+)(")/i', '$1***$3', $text) ?? $text;
    }

    private function recordResult(Courier $courier, bool $success, ?string $error = null): void
    {
        try {
            $courier->forceFill([
                'courier_api_last_checked_at' => now(),
                'courier_api_last_status'     => $success ? 'success' : 'failed',
                'courier_api_last_error'      => $success ? null : mb_substr((string) $error, 0, 1000),
            ])->save();
        } catch (\Throwable $e) {
            // Columns may not exist yet (migration not run) — don't break the main flow.
            Log::warning('Could not record courier API result', ['error' => $e->getMessage()]);
        }
    }
}
