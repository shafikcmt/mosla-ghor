<?php

namespace App\Services;

use App\Contracts\CourierConfigurationInterface;
use App\Contracts\CourierDriverInterface;
use App\Models\Courier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Pathao Courier Merchant API client (OAuth 2.0 password grant).
 *
 * Credential mapping (Admin → Courier API Settings → Pathao):
 *   api_key    = Client ID        api_secret = Client Secret
 *   username   = Pathao login email   password = Pathao login password
 *   store_id   = Pathao merchant store id (see "Get Merchant Store Info")
 *
 * Flow: POST /aladdin/api/v1/issue-token → cached Bearer token →
 *       POST /aladdin/api/v1/orders. The token is cached (not stored in DB) and
 *       re-issued automatically when it expires or Pathao answers 401.
 */
class PathaoService implements CourierDriverInterface, CourierConfigurationInterface
{
    public const LIVE_URL = 'https://api-hermes.pathao.com';
    public const SANDBOX_URL = 'https://courier-api-sandbox.pathao.com';

    private const TIMEOUT = 20;
    private const CONNECT_TIMEOUT = 10;

    public function supportsApi(): bool
    {
        return true;
    }

    public function configuration(): CourierProviderConfiguration
    {
        $secret = fn (string $label, string $type = 'password') => [
            'label' => $label, 'type' => $type, 'secret' => true,
            'required_when_enabled' => true, 'rules' => ['nullable', 'string', 'max:255'],
        ];

        return new CourierProviderConfiguration('Pathao', [
            'base_url' => [
                'label' => 'API endpoint', 'type' => 'select', 'secret' => false,
                'options' => [
                    self::LIVE_URL => self::LIVE_URL . ' (Live)',
                    self::SANDBOX_URL => self::SANDBOX_URL . ' (Sandbox / Test)',
                ],
                'default' => self::LIVE_URL, 'rules' => ['nullable', 'string'],
                'help' => 'Use Live for real parcels. Sandbox only works with Pathao test credentials.',
            ],
            'api_key' => $secret('Client ID'),
            'api_secret' => $secret('Client Secret'),
            'username' => $secret('Pathao login email', 'text'),
            'password' => $secret('Pathao login password'),
            'store_id' => [
                'label' => 'Store ID', 'type' => 'text', 'secret' => false,
                'required_when_enabled' => true, 'rules' => ['nullable', 'integer', 'min:1'],
                'help' => 'Numeric store id from your Pathao merchant panel (parcels are picked up from this store).',
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────
    // Public API
    // ─────────────────────────────────────────────────────────────────────

    /**
     * @return array{success:bool, message?:string, error?:string, tracking_id?:?string, consignment_id?:?string, status_code?:?int, data?:mixed}
     */
    public function createParcel(Courier $courier, array $payload): array
    {
        if (! $courier->api_enabled || ! $this->hasCredentials($courier)) {
            return $this->fail($courier, 'Pathao API চালু নেই অথবা Client ID/Secret, Email/Password ও Store ID কনফিগার করা হয়নি।', 'warning', errorKey: 'error');
        }

        $baseUrl = $this->baseUrl($courier);
        if ($baseUrl === null) {
            return $this->fail($courier, 'Pathao API endpoint সঠিক নয়। সেটিং থেকে Live বা Sandbox নির্বাচন করুন।', 'warning', errorKey: 'error');
        }

        $order = $this->orderPayload($courier, $payload);
        if (is_string($order)) {
            return $this->fail($courier, $order, 'warning', errorKey: 'error');
        }

        try {
            $response = $this->authorized($courier, $baseUrl, fn (PendingRequest $http, string $token) => $http
                ->withToken($token)->post($baseUrl . '/aladdin/api/v1/orders', $order));

            Log::info('Pathao create_order', [
                'courier' => $courier->name,
                'invoice' => $payload['invoice'] ?? null,
                'status'  => $response->status(),
            ]);

            $data = $response->json();
            $consignmentId = $data['data']['consignment_id'] ?? null;

            if ($response->successful() && $consignmentId) {
                $message = 'অর্ডার সফলভাবে কুরিয়ারে তৈরি হয়েছে।';
                $this->recordResult($courier, true, $message);

                return [
                    'success'        => true,
                    'message'        => $message,
                    'tracking_id'    => (string) $consignmentId,
                    'consignment_id' => (string) $consignmentId,
                    'status_code'    => $response->status(),
                    'data'           => $data,
                ];
            }

            return $this->fail($courier, $this->apiError($courier, $response), 'error', $response->status(), errorKey: 'error');
        } catch (ConnectionException $e) {
            return $this->connectionFail($courier, $e, 'error');
        } catch (\Throwable $e) {
            return $this->unexpectedFail($courier, $e, 'error');
        }
    }

    /**
     * Verifies credentials by issuing a token, then checks the configured store
     * exists in the merchant's store list (read-only; books nothing).
     */
    public function testConnection(Courier $courier): array
    {
        if (! $this->hasCredentials($courier)) {
            return $this->fail($courier, 'প্রথমে Client ID/Secret, Email/Password ও Store ID দিন এবং সংরক্ষণ করুন, তারপর টেস্ট করুন।', 'warning');
        }

        $baseUrl = $this->baseUrl($courier);
        if ($baseUrl === null) {
            return $this->fail($courier, 'Pathao API endpoint সঠিক নয়। সেটিং থেকে Live বা Sandbox নির্বাচন করুন।', 'warning');
        }

        try {
            $this->forgetToken($courier);
            $response = $this->authorized($courier, $baseUrl, fn (PendingRequest $http, string $token) => $http
                ->withToken($token)->get($baseUrl . '/aladdin/api/v1/stores'));

            if (in_array($response->status(), [400, 401, 403], true)) {
                return $this->fail($courier, 'Pathao credential ভুল বা inactive (Unauthorized)। Client ID/Secret ও Email/Password যাচাই করুন, এবং Live/Sandbox endpoint মিলিয়ে নিন।', 'warning', $response->status());
            }

            if (! $response->successful()) {
                return $this->fail($courier, $this->apiError($courier, $response), 'error', $response->status());
            }

            $stores = $response->json('data.data') ?? [];
            $ids = array_map(fn ($store) => (string) ($store['store_id'] ?? ''), $stores);
            $store = collect($stores)->first(fn ($s) => (string) ($s['store_id'] ?? '') === (string) $courier->store_id);

            if ($store === null) {
                return $this->fail($courier, 'টোকেন পাওয়া গেছে কিন্তু Store ID ' . $courier->store_id . ' এই অ্যাকাউন্টে নেই। সঠিক Store ID দিন'
                    . ($ids ? ' (উপলব্ধ: ' . implode(', ', array_filter($ids)) . ')' : '') . '।', 'warning', $response->status());
            }

            if (isset($store['is_active']) && (int) $store['is_active'] !== 1) {
                return $this->fail($courier, 'Store ID ' . $courier->store_id . ' এখনো active/approved নয়। Pathao অনুমোদনের জন্য অপেক্ষা করুন।', 'warning', $response->status());
            }

            $message = 'সংযোগ সফল হয়েছে — API সক্রিয়।' . (! empty($store['store_name']) ? ' Store: ' . $store['store_name'] : '');
            $this->recordResult($courier, true, $message);

            return ['success' => true, 'message' => $message, 'level' => 'success', 'status_code' => $response->status(), 'data' => null];
        } catch (ConnectionException $e) {
            return $this->connectionFail($courier, $e);
        } catch (\Throwable $e) {
            return $this->unexpectedFail($courier, $e);
        }
    }

    // ─────────────────────────────────────────────────────────────────────
    // Payload
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Map the common courier payload to a Pathao order. Returns an error string
     * when Pathao's documented field limits would reject it.
     *
     * @return array<string,mixed>|string
     */
    private function orderPayload(Courier $courier, array $payload): array|string
    {
        $name = trim((string) ($payload['recipient_name'] ?? ''));
        if (mb_strlen($name) < 3) {
            return 'Pathao: প্রাপকের নাম কমপক্ষে ৩ অক্ষরের হতে হবে।';
        }

        $phone = $this->normalizePhone((string) ($payload['recipient_phone'] ?? ''));
        if ($phone === null) {
            return 'Pathao: প্রাপকের মোবাইল নম্বর ১১ ডিজিটের (01XXXXXXXXX) হতে হবে।';
        }

        $address = trim((string) ($payload['recipient_address'] ?? ''));
        if (mb_strlen($address) < 10) {
            return 'Pathao: প্রাপকের ঠিকানা কমপক্ষে ১০ অক্ষরের হতে হবে।';
        }

        $order = [
            'store_id'          => (int) $courier->store_id,
            'merchant_order_id' => (string) ($payload['invoice'] ?? ''),
            'recipient_name'    => mb_substr($name, 0, 100),
            'recipient_phone'   => $phone,
            'recipient_address' => mb_substr($address, 0, 220),
            'delivery_type'     => 48,   // Normal delivery
            'item_type'         => 2,    // Parcel
            'item_quantity'     => max(1, (int) ($payload['item_quantity'] ?? 1)),
            'item_weight'       => (string) min(10, max(0.5, (float) ($payload['item_weight'] ?? 0.5))),
            'amount_to_collect' => (int) round((float) ($payload['cod_amount'] ?? 0)),
        ];

        if (filled($payload['note'] ?? null)) {
            $order['special_instruction'] = (string) $payload['note'];
        }
        if (filled($payload['invoice'] ?? null)) {
            $order['item_description'] = 'Order ' . $payload['invoice'];
        }

        return $order;
    }

    /** Pathao requires exactly 11 digits: accept +8801…, 8801…, 01… */
    private function normalizePhone(string $raw): ?string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        if (str_starts_with($digits, '880') && strlen($digits) === 13) {
            $digits = substr($digits, 2);
        }

        return preg_match('/^01\d{9}$/', $digits) ? $digits : null;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Auth + HTTP
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Run a request with a cached token; on 401 re-issue the token once and retry.
     *
     * @param  callable(PendingRequest,string):Response  $send
     */
    private function authorized(Courier $courier, string $baseUrl, callable $send): Response
    {
        $response = $send($this->client(), $this->token($courier, $baseUrl));

        if ($response->status() === 401) {
            $this->forgetToken($courier);
            $response = $send($this->client(), $this->token($courier, $baseUrl));
        }

        return $response;
    }

    private function token(Courier $courier, string $baseUrl): string
    {
        $key = $this->tokenKey($courier);

        if ($cached = Cache::get($key)) {
            return $cached;
        }

        $response = $this->client()->post($baseUrl . '/aladdin/api/v1/issue-token', [
            'client_id'     => $courier->api_key,
            'client_secret' => $courier->api_secret,
            'grant_type'    => 'password',
            'username'      => $courier->username,
            'password'      => $courier->password,
        ]);

        $token = $response->successful() ? $response->json('access_token') : null;
        if (! $token) {
            throw new PathaoAuthException($response->status());
        }

        // Refresh a day early; Pathao tokens live ~5 days (expires_in seconds).
        $ttl = max(300, (int) $response->json('expires_in', 432000) - 86400);
        Cache::put($key, $token, $ttl);

        return $token;
    }

    private function forgetToken(Courier $courier): void
    {
        Cache::forget($this->tokenKey($courier));
    }

    /** Key changes whenever credentials/endpoint change, so stale tokens are never reused. */
    private function tokenKey(Courier $courier): string
    {
        return 'pathao_token_' . $courier->id . '_' . hash('sha256', implode('|', [
            $courier->api_key, $courier->api_secret, $courier->username, $courier->password, $courier->base_url,
        ]));
    }

    private function client(): PendingRequest
    {
        return Http::withoutRedirecting()
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->timeout(self::TIMEOUT)
            ->acceptJson()
            ->asJson();
    }

    private function hasCredentials(Courier $courier): bool
    {
        return filled($courier->api_key) && filled($courier->api_secret)
            && filled($courier->username) && filled($courier->password)
            && filled($courier->store_id);
    }

    private function baseUrl(Courier $courier): ?string
    {
        $url = rtrim(trim((string) ($courier->base_url ?: self::LIVE_URL)), '/');

        return in_array($url, [self::LIVE_URL, self::SANDBOX_URL], true) ? $url : null;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Errors / bookkeeping
    // ─────────────────────────────────────────────────────────────────────

    private function apiError(Courier $courier, Response $response): string
    {
        $detail = $response->json('message');
        $errors = $response->json('errors');
        if (is_array($errors) && $errors) {
            $first = reset($errors);
            $detail = is_array($first) ? reset($first) : $first;
        }
        $detail = is_string($detail) ? mb_substr($this->redact($courier, $detail), 0, 200) : null;

        return 'Pathao API ত্রুটি (' . $response->status() . ')' . ($detail ? ': ' . $detail : '।');
    }

    private function connectionFail(Courier $courier, \Throwable $e, string $errorKey = 'message'): array
    {
        $raw = $this->redact($courier, $e->getMessage());
        Log::error('Pathao connection error', ['courier' => $courier->name, 'error' => $raw]);

        $message = match (true) {
            (bool) preg_match('/could not resolve host|curl error 6(?![0-9])|name or service not known/i', $raw)
                => 'Pathao API host resolve করা যাচ্ছে না। Server DNS / hosting outbound connection check করুন।',
            (bool) preg_match('/timed out|timeout|curl error 28(?![0-9])/i', $raw)
                => 'Pathao API connection timeout হয়েছে।',
            (bool) preg_match('/ssl|certificate|curl error 60(?![0-9])|curl error 35(?![0-9])/i', $raw)
                => 'Pathao সার্ভারের সাথে নিরাপদ (SSL) সংযোগ করা যায়নি।',
            default => 'Pathao সার্ভারে সংযোগ করা যায়নি। নেটওয়ার্ক / hosting outbound connection check করুন।',
        };

        return $this->fail($courier, $message, 'error', errorKey: $errorKey, technical: $raw);
    }

    private function unexpectedFail(Courier $courier, \Throwable $e, string $errorKey = 'message'): array
    {
        if ($e instanceof PathaoAuthException) {
            return $this->fail(
                $courier,
                'Pathao টোকেন পাওয়া যায়নি (HTTP ' . $e->status . ')। Client ID/Secret ও Email/Password ঠিক আছে কিনা এবং Live/Sandbox endpoint মিলছে কিনা দেখুন।',
                'warning', $e->status ?: null, errorKey: $errorKey
            );
        }

        Log::error('Pathao unexpected error', ['courier' => $courier->name, 'error' => $this->redact($courier, $e->getMessage())]);

        return $this->fail(
            $courier,
            'Pathao API কল করার সময় একটি সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন বা লগ দেখুন।',
            'error', errorKey: $errorKey, technical: $this->redact($courier, $e->getMessage())
        );
    }

    private function fail(Courier $courier, string $message, string $level, ?int $statusCode = null, string $errorKey = 'message', ?string $technical = null): array
    {
        $this->recordResult($courier, false, $message, $technical);

        $result = ['success' => false, 'message' => $message, 'level' => $level, 'status_code' => $statusCode, 'data' => null];
        if ($errorKey === 'error') {
            $result['error'] = $message;
        }

        return $result;
    }

    private function redact(Courier $courier, ?string $text): string
    {
        return str_replace(
            array_filter([$courier->api_key, $courier->api_secret, $courier->username, $courier->password], fn ($v) => filled($v)),
            '••••••••',
            (string) $text
        );
    }

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
            Log::warning('Could not record courier API result', ['courier_id' => $courier->id]);
        }
    }
}
