<?php

namespace App\Services;

use App\Models\Courier;
use App\Models\Order;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Steadfast Courier API client.
 *
 * Headers: Api-Key + Secret-Key (per Steadfast bulk API v1).
 * Base URL: https://portal.steadfast.com.bd/api/v1
 *
 * ── Live-server debugging for "Could not resolve host" (cURL error 6) ──
 * This error means the PHP/host machine cannot resolve the Steadfast domain
 * over DNS. It is almost always a hosting/network issue, NOT a code bug.
 * From the cPanel / SSH terminal on the live server, run:
 *
 *     php -r "echo gethostbyname('portal.steadfast.com.bd').PHP_EOL;"
 *     curl -I https://portal.steadfast.com.bd/api/v1/get_balance
 *
 * If gethostbyname() returns the same hostname (not an IP) or curl fails with
 * "Could not resolve host", the server has no working DNS / outbound HTTPS.
 * Ask the hosting provider to enable outbound connections and fix DNS.
 */
class SteadfastService
{
    private const DEFAULT_BASE_URL = 'https://portal.steadfast.com.bd/api/v1';
    private const TIMEOUT = 20;
    private const CONNECT_TIMEOUT = 10;

    /**
     * Create a delivery consignment at Steadfast for the given order.
     *
     * @return array{success:bool, message?:string, error?:string, tracking_id?:?string, consignment_id?:?string, status_code?:?int, data?:mixed}
     */
    public function createOrder(Courier $courier, Order $order): array
    {
        if (! $courier->api_enabled || ! $courier->api_key || ! $courier->api_secret) {
            return $this->fail($courier, 'Steadfast API চালু নেই অথবা API Key/Secret কনফিগার করা হয়নি।', 'warning', errorKey: 'error');
        }

        $baseUrl = $this->normalizeBaseUrl($courier->base_url);
        if ($baseUrl === null) {
            return $this->fail($courier, 'Steadfast Base URL সঠিক নয়। সঠিক ফরম্যাটে দিন: ' . self::DEFAULT_BASE_URL, 'warning', errorKey: 'error');
        }

        $endpoint = $this->buildUrl($baseUrl, 'create_order');

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
            $response = $this->client($courier)->post($endpoint, $payload);

            // NOTE: never log api_key/api_secret — only the payload + sanitized response.
            Log::info('Steadfast create_order', [
                'courier'  => $courier->name,
                'order'    => $order->order_number,
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $this->sanitize($response->body()),
            ]);

            if ($response->successful()) {
                $data        = $response->json();
                $consignment = $data['consignment'] ?? [];

                // Steadfast returns status=200 in the body on success.
                if (($data['status'] ?? null) == 200 && ! empty($consignment)) {
                    $this->recordResult($courier, true, 'অর্ডার সফলভাবে কুরিয়ারে তৈরি হয়েছে।');

                    return [
                        'success'        => true,
                        'message'        => 'অর্ডার সফলভাবে কুরিয়ারে তৈরি হয়েছে।',
                        'tracking_id'    => $consignment['tracking_code'] ?? null,
                        'consignment_id' => (string) ($consignment['consignment_id'] ?? $consignment['id'] ?? ''),
                        'status_code'    => $response->status(),
                        'data'           => $data,
                    ];
                }

                $message = $this->friendlyApiError($data, $response->body());

                return $this->fail($courier, $message, 'error', $response->status(), $data, errorKey: 'error');
            }

            return $this->handleHttpError($courier, $response, $endpoint, errorKey: 'error');
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($courier, $e, $baseUrl, $endpoint, errorKey: 'error');
        } catch (\Throwable $e) {
            return $this->handleUnexpected($courier, $e, $baseUrl, $endpoint, errorKey: 'error');
        }
    }

    /**
     * Lightweight connectivity / credential check via the read-only balance endpoint.
     *
     * @return array{success:bool, message:string, level:string, status_code:?int, data:mixed}
     */
    public function testConnection(Courier $courier): array
    {
        if (! $courier->api_key || ! $courier->api_secret) {
            return $this->fail($courier, 'প্রথমে API Key এবং Secret Key দিন এবং সংরক্ষণ করুন, তারপর টেস্ট করুন।', 'warning');
        }

        $baseUrl = $this->normalizeBaseUrl($courier->base_url);
        if ($baseUrl === null) {
            return $this->fail($courier, 'Base URL সঠিক নয়। সঠিক ফরম্যাটে দিন: ' . self::DEFAULT_BASE_URL, 'warning');
        }

        $endpoint = $this->buildUrl($baseUrl, 'get_balance');

        try {
            $response = $this->client($courier)->get($endpoint);

            Log::info('Steadfast test_connection', [
                'courier'  => $courier->name,
                'endpoint' => $endpoint,
                'status'   => $response->status(),
                'body'     => $this->sanitize($response->body()),
            ]);

            if ($response->successful()
                && ($response->json('status') == 200 || $response->json('current_balance') !== null)) {
                $balance = $response->json('current_balance');
                $message = 'সংযোগ সফল হয়েছে — API সক্রিয়।'
                    . ($balance !== null ? ' বর্তমান ব্যালেন্স: ৳' . number_format((float) $balance, 2) : '');

                $this->recordResult($courier, true, $message);

                return [
                    'success'     => true,
                    'message'     => $message,
                    'level'       => 'success',
                    'status_code' => $response->status(),
                    'data'        => $response->json(),
                ];
            }

            if (in_array($response->status(), [401, 403], true)) {
                return $this->fail(
                    $courier,
                    'API Key অথবা Secret Key ভুল বা inactive (Unauthorized)।',
                    'warning',
                    $response->status(),
                    $response->json()
                );
            }

            return $this->handleHttpError($courier, $response, $endpoint);
        } catch (ConnectionException $e) {
            return $this->handleConnectionError($courier, $e, $baseUrl, $endpoint);
        } catch (\Throwable $e) {
            return $this->handleUnexpected($courier, $e, $baseUrl, $endpoint);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Internal helpers
    // ─────────────────────────────────────────────────────────────────────

    private function client(Courier $courier)
    {
        return Http::withHeaders($this->headers($courier))
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::TIMEOUT)
            ->acceptJson();
    }

    private function headers(Courier $courier): array
    {
        return [
            'Api-Key'      => $courier->api_key,
            'Secret-Key'   => $courier->api_secret,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    /**
     * Clean + validate a base URL. Returns a normalized URL with scheme and no
     * trailing slash, or null if it cannot be made into a valid http(s) URL.
     */
    public function normalizeBaseUrl(?string $raw): ?string
    {
        $raw = ($raw === null || trim($raw) === '') ? self::DEFAULT_BASE_URL : $raw;

        // Strip control chars, zero-width spaces, BOM; then trim.
        $url = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200D}\x{FEFF}]/u', '', $raw);
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        // Default to https:// when no scheme is present.
        if (! preg_match('~^https?://~i', $url)) {
            $url = 'https://' . $url;
        }

        $url = rtrim($url, '/');

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (empty($host)) {
            return null;
        }

        return $url;
    }

    /**
     * Join a base URL and an endpoint with exactly one slash.
     */
    private function buildUrl(string $base, string $endpoint): string
    {
        return rtrim($base, '/') . '/' . ltrim($endpoint, '/');
    }

    /**
     * Map a connection failure (DNS, timeout, refused) to a friendly result.
     */
    private function handleConnectionError(Courier $courier, \Throwable $e, string $baseUrl, string $endpoint, string $errorKey = 'message'): array
    {
        $raw = $e->getMessage();

        Log::error('Steadfast connection error', [
            'courier'   => $courier->name,
            'base_url'  => $baseUrl,
            'endpoint'  => $endpoint,
            'curl_error'=> $raw, // never contains the secret — headers are not in the message
            'timestamp' => now()->toDateTimeString(),
        ]);

        // cURL 6 / DNS resolution failure.
        if (preg_match('/could not resolve host|curl error 6|name or service not known|resolve host/i', $raw)) {
            $message = 'Steadfast API host resolve করা যাচ্ছে না। Base URL, server DNS অথবা hosting outbound connection check করুন।';
        } elseif (preg_match('/timed out|timeout|operation timed out|curl error 28/i', $raw)) {
            $message = 'Steadfast সার্ভারে সংযোগ টাইমআউট হয়েছে। কিছুক্ষণ পর আবার চেষ্টা করুন।';
        } elseif (preg_match('/subject (alt(ernative)? )?name|subject name|does ?n[\'o]t match|host ?name mismatch|curl error 51/i', $raw)) {
            // SSL handshake succeeded but the cert does not cover this hostname.
            // (e.g. "SSL: no alternative certificate subject name matches target host name")
            // Do NOT disable SSL verification — this is a domain/SNI/hosting issue.
            $message = 'Steadfast API SSL certificate host mismatch. Base URL/domain Steadfast support থেকে confirm করুন অথবা hosting SSL/SNI issue check করুন।';
        } elseif (preg_match('/ssl|certificate|curl error 60|curl error 35/i', $raw)) {
            $message = 'Steadfast সার্ভারের সাথে নিরাপদ (SSL) সংযোগ করা যায়নি। সার্ভারের SSL/CA সেটিং check করুন।';
        } elseif (preg_match('/connection refused|could not connect|failed to connect|curl error 7/i', $raw)) {
            $message = 'Steadfast সার্ভারে সংযোগ করা যায়নি (connection refused)। hosting outbound connection check করুন।';
        } else {
            $message = 'Steadfast সার্ভারে সংযোগ করা যায়নি। নেটওয়ার্ক / hosting outbound connection check করুন।';
        }

        return $this->fail($courier, $message, 'error', errorKey: $errorKey, technical: $raw);
    }

    private function handleHttpError(Courier $courier, $response, string $endpoint, string $errorKey = 'message'): array
    {
        $message = $this->friendlyApiError($response->json(), $response->body(), $response->status());

        return $this->fail($courier, $message, 'error', $response->status(), $response->json(), errorKey: $errorKey);
    }

    private function handleUnexpected(Courier $courier, \Throwable $e, string $baseUrl, string $endpoint, string $errorKey = 'message'): array
    {
        Log::error('Steadfast unexpected error', [
            'courier'   => $courier->name,
            'base_url'  => $baseUrl,
            'endpoint'  => $endpoint,
            'error'     => $e->getMessage(),
            'timestamp' => now()->toDateTimeString(),
        ]);

        return $this->fail(
            $courier,
            'Steadfast API কল করার সময় একটি সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন বা লগ দেখুন।',
            'error',
            errorKey: $errorKey,
            technical: $e->getMessage()
        );
    }

    /**
     * Build a structured failure result, record it on the courier, and return it.
     * $errorKey controls the result key holding the message: testConnection()
     * consumers read 'message'; createOrder() consumers read 'error'.
     */
    private function fail(Courier $courier, string $message, string $level, ?int $statusCode = null, $data = null, string $errorKey = 'message', ?string $technical = null): array
    {
        $this->recordResult($courier, false, $message, $technical);

        $result = [
            'success'     => false,
            'message'     => $message,
            'level'       => $level,
            'status_code' => $statusCode,
            'data'        => $data,
        ];

        if ($errorKey === 'error') {
            $result['error'] = $message;
        }

        return $result;
    }

    /**
     * Turn a Steadfast error payload into a readable single-line message.
     */
    private function friendlyApiError(?array $data, ?string $rawBody, ?int $status = null): string
    {
        $prefix = $status ? "Steadfast API ত্রুটি ($status): " : 'Steadfast API ত্রুটি: ';

        if (is_array($data) && ! empty($data['message']) && is_string($data['message'])) {
            $msg = $data['message'];

            // Laravel-style validation errors → flatten.
            if (! empty($data['errors']) && is_array($data['errors'])) {
                $flat = [];
                foreach ($data['errors'] as $messages) {
                    $flat[] = is_array($messages) ? implode(', ', $messages) : (string) $messages;
                }
                if ($flat) {
                    $msg .= ' (' . implode('; ', $flat) . ')';
                }
            }

            return $prefix . $msg;
        }

        $body = trim($this->sanitize($rawBody ?? ''));

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

    /**
     * Persist the last test/send outcome. Tolerant of missing columns so it
     * never breaks the main flow before migrations are run.
     */
    private function recordResult(Courier $courier, bool $success, ?string $message = null, ?string $technical = null): void
    {
        try {
            $courier->forceFill([
                'courier_api_last_checked_at' => now(),
                'courier_api_last_status'     => $success ? 'success' : 'failed',
                'courier_api_last_message'    => $message !== null ? mb_substr($message, 0, 1000) : null,
                'courier_api_last_error'      => $success ? null : mb_substr((string) ($technical ?? $message), 0, 1000),
            ])->save();
        } catch (\Throwable $e) {
            Log::warning('Could not record courier API result', ['error' => $e->getMessage()]);
        }
    }
}
