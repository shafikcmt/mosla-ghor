<?php

namespace App\Services;

use App\Contracts\CourierDiagnosticsInterface;
use App\Contracts\CourierDriverInterface;
use App\Models\Courier;
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
class SteadfastService implements CourierDriverInterface, CourierDiagnosticsInterface
{
    private const DEFAULT_BASE_URL = 'https://portal.steadfast.com.bd/api/v1';
    private const TIMEOUT = 20;
    private const CONNECT_TIMEOUT = 10;

    /** Known Steadfast/Packzy API base URLs offered in the admin dropdown. */
    public const KNOWN_BASE_URLS = [
        'https://portal.steadfast.com.bd/api/v1',
        'https://portal.packzy.com/api/v1',
    ];

    public function supportsApi(): bool
    {
        return true;
    }

    /**
     * Create a Steadfast consignment from a ready-made payload. The common
     * CourierService builds the payload (retail order or vendor parcel); this
     * driver only knows Steadfast's endpoint, headers and response shape.
     *
     * Required payload keys: invoice, recipient_name, recipient_phone,
     * recipient_address, cod_amount, note.
     *
     * @return array{success:bool, message?:string, error?:string, tracking_id?:?string, consignment_id?:?string, status_code?:?int, data?:mixed}
     */
    public function createParcel(Courier $courier, array $payload): array
    {
        if (! $courier->api_enabled || ! $courier->api_key || ! $courier->api_secret) {
            return $this->fail($courier, 'Steadfast API চালু নেই অথবা API Key/Secret কনফিগার করা হয়নি।', 'warning', errorKey: 'error');
        }

        $baseUrl = $this->normalizeBaseUrl($courier->base_url);
        if ($baseUrl === null) {
            return $this->fail($courier, 'Steadfast Base URL সঠিক নয়। সঠিক ফরম্যাটে দিন: ' . self::DEFAULT_BASE_URL, 'warning', errorKey: 'error');
        }

        $endpoint = $this->buildUrl($baseUrl, 'create_order');

        try {
            $response = $this->client($courier)->post($endpoint, $payload);

            // NOTE: never log api_key/api_secret — only the invoice + sanitized response.
            Log::info('Steadfast create_order', [
                'courier'  => $courier->name,
                'invoice'  => $payload['invoice'] ?? null,
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
    // Diagnostics (DNS / SSL / Balance / Full) — admin only
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Resolve the API host over DNS only — no HTTP. Distinguishes a local DNS
     * problem from a server one (the UI explains the difference).
     *
     * @return array{success:bool, message:string, level:string, detail:?string}
     */
    public function testDns(Courier $courier): array
    {
        $host = parse_url($this->normalizeBaseUrl($courier->base_url) ?? self::DEFAULT_BASE_URL, PHP_URL_HOST);

        if (empty($host)) {
            return $this->diag(false, 'Base URL সঠিক নয় — host পাওয়া যায়নি।', 'error');
        }

        $ip = @gethostbyname($host);

        // gethostbyname returns the input unchanged on failure.
        if ($ip === $host || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return $this->diag(
                false,
                'API host resolve করা যাচ্ছে না। Local/server DNS অথবা hosting outbound connection check করুন।'
                    . ' (host: ' . $host . ')'
                    . ' — Local network থেকে resolve না হলে live server-এ আলাদাভাবে check করুন।',
                'error',
                'host=' . $host
            );
        }

        return $this->diag(true, 'DNS OK — ' . $host . ' → ' . $ip, 'success');
    }

    /**
     * Verify the TLS handshake to the API host. Any HTTP response (even 401)
     * means SSL is fine; a connection-level SSL error is classified clearly.
     *
     * @return array{success:bool, message:string, level:string, detail:?string}
     */
    public function testSsl(Courier $courier): array
    {
        $baseUrl = $this->normalizeBaseUrl($courier->base_url);
        if ($baseUrl === null) {
            return $this->diag(false, 'Base URL সঠিক নয়।', 'error');
        }

        $endpoint = $this->buildUrl($baseUrl, 'get_balance');

        try {
            $response = $this->client($courier)->get($endpoint);

            return $this->diag(true, 'SSL / সংযোগ ঠিক আছে (HTTP ' . $response->status() . ')।', 'success');
        } catch (ConnectionException $e) {
            $raw = $e->getMessage();
            $message = $this->classifyConnectionError($raw);
            $this->recordResult($courier, false, $message, $raw);

            return $this->diag(false, $message, 'error', $raw);
        } catch (\Throwable $e) {
            return $this->diag(false, 'SSL/সংযোগ যাচাই ব্যর্থ হয়েছে।', 'error', $e->getMessage());
        }
    }

    /**
     * Run DNS → SSL → Balance in order, stopping at the first failure.
     */
    public function fullTest(Courier $courier): array
    {
        $dns = $this->testDns($courier);
        if (! $dns['success']) {
            return $dns;
        }

        $ssl = $this->testSsl($courier);
        if (! $ssl['success']) {
            return $ssl;
        }

        // Balance check exercises credentials too; reuse the existing path.
        return $this->testConnection($courier);
    }

    private function diag(bool $success, string $message, string $level, ?string $detail = null): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'level'   => $level,
            'detail'  => $detail,
        ];
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

        return $this->fail($courier, $this->classifyConnectionError($raw), 'error', errorKey: $errorKey, technical: $raw);
    }

    /**
     * Map a raw cURL/connection error string to a friendly Bangla message.
     * Shared by the send/test paths and the diagnostics endpoint.
     */
    public function classifyConnectionError(string $raw): string
    {
        // SSL hostname/subject mismatch — check BEFORE the generic SSL and DNS
        // branches. Handshake reached the server but the cert doesn't cover the host.
        // (e.g. "SSL: no alternative certificate subject name matches target host name")
        // Do NOT disable SSL verification — this is a domain/SNI/hosting issue.
        // (?![0-9]) keeps "curl error 51" from matching "curl error 51x".
        if (preg_match('/subject (alt(ernative)? )?name|subject name|does ?n[\'o]t match|host ?name mismatch|curl error 51(?![0-9])/i', $raw)) {
            return 'API SSL certificate hostname match করছে না। Base URL/Steadfast endpoint confirm করুন।';
        }
        // Generic SSL/cert failure (cURL 60/35). Checked before DNS so "curl error 60"
        // is not mis-classified by a looser pattern.
        if (preg_match('/ssl|certificate|curl error 60(?![0-9])|curl error 35(?![0-9])/i', $raw)) {
            return 'Steadfast সার্ভারের সাথে নিরাপদ (SSL) সংযোগ করা যায়নি। সার্ভারের SSL/CA সেটিং check করুন।';
        }
        // cURL 6 / DNS resolution failure. (?![0-9]) so "curl error 6" ≠ "curl error 60".
        if (preg_match('/could not resolve host|curl error 6(?![0-9])|name or service not known|resolve host/i', $raw)) {
            return 'API host resolve করা যাচ্ছে না। Local/server DNS অথবা hosting outbound connection check করুন।';
        }
        if (preg_match('/timed out|timeout|operation timed out|curl error 28(?![0-9])/i', $raw)) {
            return 'API connection timeout হয়েছে।';
        }
        if (preg_match('/connection refused|could not connect|failed to connect|curl error 7(?![0-9])/i', $raw)) {
            return 'Steadfast সার্ভারে সংযোগ করা যায়নি (connection refused)। hosting outbound connection check করুন।';
        }

        return 'Steadfast সার্ভারে সংযোগ করা যায়নি। নেটওয়ার্ক / hosting outbound connection check করুন।';
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
