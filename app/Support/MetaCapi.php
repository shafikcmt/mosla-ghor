<?php

namespace App\Support;

use App\Jobs\SendMetaCapiEvents;
use App\Models\BotLead;
use App\Models\MarketingSetting;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Meta Conversions API — server-side copies of the PLATFORM pixel events (Phase B).
 *
 * Rules:
 *  - Only the platform pixel(s); vendor pixels stay browser-only.
 *  - Same event_name + event_id as the browser event, so Meta de-duplicates the pair.
 *  - Same platform scope as the browser (MetaPixel::platform* helpers).
 *  - Customer identifiers are SHA-256 hashed; the access token is never logged or output.
 *  - Sending happens after the response is sent, so a slow/failed Meta call never
 *    delays or breaks checkout.
 */
class MetaCapi
{
    public const GRAPH_URL = 'https://graph.facebook.com';

    /** CAPI is configured and this visitor may be tracked (same admin exclusion as the pixel). */
    public static function enabled(): bool
    {
        $settings = MetaPixel::settings();
        return $settings
            && $settings->capi_enabled
            && self::token($settings) !== null
            && MetaPixel::platformIds() !== []
            && MetaPixel::trackingAllowed();
    }

    /** Decrypted token, or null (missing, or unreadable after an APP_KEY change). */
    public static function token(?MarketingSetting $settings): ?string
    {
        if (! $settings) {
            return null;
        }
        try {
            $token = $settings->capi_access_token;
        } catch (\Throwable) {
            return null;
        }
        return is_string($token) && $token !== '' ? $token : null;
    }

    // ── Events ──────────────────────────────────────────────────────────────

    /** Purchase at order time (server never depends on the customer reaching the success page). */
    public static function purchase(Order $order, ?string $email = null): void
    {
        self::safely(function () use ($order, $email) {
            if (! self::enabled() || ! ($params = MetaPixel::platformPurchaseParams($order))) {
                return;
            }
            self::queue([self::event('Purchase', (string) $order->order_number, $params, self::userData([
                'phone' => $order->mobile_number,
                'email' => $email,
                'name' => $order->customer_name,
                'external_id' => $order->customer_id,
            ]), route('order.success', $order->order_number))]);
        });
    }

    public static function leadForProduct(Product $product, string $eventId, array $contact): void
    {
        self::safely(function () use ($product, $eventId, $contact) {
            if (! self::enabled() || ! MetaPixel::platformAllowsProduct($product->vendor_id)) {
                return;
            }
            self::queue([self::event('Lead', $eventId, MetaPixel::productLeadParams($product), self::userData($contact),
                route('products.show', $product->slug))]);
        });
    }

    /** @param array $productVendorIds product_id => vendor_id|null */
    public static function leadForCombo(array $productVendorIds, string $eventId, array $contact): void
    {
        self::safely(function () use ($productVendorIds, $eventId, $contact) {
            if (! self::enabled() || ! ($ids = MetaPixel::platformComboIds($productVendorIds))) {
                return;
            }
            self::queue([self::event('Lead', $eventId,
                ['content_ids' => $ids, 'content_type' => 'product_group', 'currency' => MetaPixel::CURRENCY],
                self::userData($contact))]);
        });
    }

    public static function completeRegistration(string $eventId, array $contact): void
    {
        self::safely(function () use ($eventId, $contact) {
            if (! self::enabled()) {
                return;
            }
            self::queue([self::event('CompleteRegistration', $eventId, ['status' => true], self::userData($contact))]);
        });
    }

    /**
     * Lead captured by the social bot (Messenger / comments / WhatsApp). No browser
     * counterpart, and the request comes from the bot server, so no IP/UA/cookies.
     */
    public static function botLead(BotLead $lead): void
    {
        self::safely(function () use ($lead) {
            if (! self::enabled()) {
                return;
            }
            $params = ['currency' => MetaPixel::CURRENCY, 'lead_source' => $lead->source];
            if ($lead->product && MetaPixel::platformAllowsProduct($lead->product->vendor_id)) {
                $params = array_merge(MetaPixel::productLeadParams($lead->product), ['lead_source' => $lead->source]);
            }
            $event = self::event('Lead', 'bot-lead-'.$lead->id, $params, self::userData([
                'phone' => $lead->phone, 'name' => $lead->customer_name,
            ], false), null, 'chat');
            self::queue([$event]);
        });
    }

    // ── Building ────────────────────────────────────────────────────────────

    public static function event(string $name, string $eventId, array $customData, array $userData,
        ?string $sourceUrl = null, string $actionSource = 'website'): array
    {
        if ($actionSource === 'website') {
            $sourceUrl ??= request()->headers->get('referer') ?: url('/');
        }
        return array_filter([
            'event_name' => $name,
            'event_time' => time(),
            'event_id' => $eventId,
            'action_source' => $actionSource,
            'event_source_url' => $sourceUrl,
            'user_data' => $userData,
            'custom_data' => $customData,
        ], fn ($v) => $v !== null);
    }

    /**
     * Hashed customer identifiers (+ browser context from the current request when
     * $withRequest). Keys follow Meta's user_data spec.
     */
    public static function userData(array $contact, bool $withRequest = true): array
    {
        $data = ['country' => [self::hash('bd')]];

        if (($phone = Phone::normalize($contact['phone'] ?? null)) && Phone::isValidBd($phone)) {
            $data['ph'] = [self::hash(Phone::toWa($phone))];
        }
        $email = strtolower(trim((string) ($contact['email'] ?? '')));
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $data['em'] = [self::hash($email)];
        }
        $first = preg_split('/\s+/u', trim((string) ($contact['name'] ?? '')))[0] ?? '';
        $first = mb_strtolower(preg_replace('/[\p{P}\p{S}\d]+/u', '', $first));
        if ($first !== '') {
            $data['fn'] = [self::hash($first)];
        }
        if (! empty($contact['external_id'])) {
            $data['external_id'] = [self::hash((string) $contact['external_id'])];
        }

        if ($withRequest) {
            $request = request();
            $data['client_ip_address'] = $request->ip();
            $data['client_user_agent'] = (string) $request->userAgent();
            foreach (['fbp' => '_fbp', 'fbc' => '_fbc'] as $key => $cookie) {
                $value = $request->cookie($cookie);
                if (is_string($value) && preg_match('/^fb\.\d\.\d+\.[\w\-.]+$/', $value)) {
                    $data[$key] = $value;
                }
            }
            if (empty($data['fbc']) && ($clid = $request->query('fbclid')) && is_string($clid) && preg_match('/^[\w\-]+$/', $clid)) {
                $data['fbc'] = 'fb.1.'.(int) (microtime(true) * 1000).'.'.$clid;
            }
        }

        return array_filter($data, fn ($v) => $v !== '' && $v !== null);
    }

    public static function hash(string $value): string
    {
        return hash('sha256', $value);
    }

    // ── Sending ─────────────────────────────────────────────────────────────

    /** Send after the response (never delays the customer). */
    public static function queue(array $events): void
    {
        SendMetaCapiEvents::dispatchAfterResponse($events);
    }

    /**
     * POST events to every platform pixel. Records the last success / error on the
     * settings row for the admin page. Returns [ok, message].
     */
    public static function send(array $events, bool $includeTestCode = true): array
    {
        $settings = MarketingSetting::query()->first();
        $token = self::token($settings);
        $pixels = array_values(array_filter((array) $settings?->pixel_ids, fn ($id) => MetaPixel::validId((string) $id)));
        if (! $settings || ! $token || ! $pixels || ! $events) {
            return [false, 'Conversions API কনফিগার করা নেই (Pixel ID / Access token)।'];
        }

        $version = preg_match('/^v\d+\.\d+$/', (string) config('services.meta.graph_version')) ? config('services.meta.graph_version') : 'v23.0';
        $payload = array_filter([
            'data' => json_encode(array_values($events), JSON_UNESCAPED_UNICODE),
            'access_token' => $token,
            'test_event_code' => $includeTestCode ? $settings->test_event_code : null,
        ]);

        $ok = true;
        $message = '';
        foreach ($pixels as $pixel) {
            try {
                $response = Http::asForm()->timeout(8)->post(self::GRAPH_URL."/{$version}/{$pixel}/events", $payload);
                if ($response->successful() && ! $response->json('error')) {
                    $message = 'Meta গ্রহণ করেছে: '.(int) $response->json('events_received').'টি ইভেন্ট।';
                    continue;
                }
                $ok = false;
                $message = 'Pixel '.$pixel.': '.mb_substr((string) ($response->json('error.error_user_msg')
                    ?: $response->json('error.message') ?: 'HTTP '.$response->status()), 0, 400);
            } catch (\Throwable $e) {
                $ok = false;
                $message = 'Pixel '.$pixel.': Meta সার্ভারে পৌঁছানো যায়নি ('.class_basename($e).').';
            }
        }

        try {
            MarketingSetting::query()->whereKey($settings->id)->update($ok
                ? ['capi_last_success_at' => now()]
                : ['capi_last_error' => $message, 'capi_last_error_at' => now()]);
        } catch (\Throwable) {
            // status metadata only
        }
        if (! $ok) {
            Log::warning('Meta CAPI send failed.', ['error' => $message, 'events' => array_column($events, 'event_name')]);
        }

        return [$ok, $message];
    }

    /** Tracking must never break the business action around it. */
    private static function safely(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            Log::warning('Meta CAPI event skipped.', ['error' => get_class($e).': '.$e->getMessage()]);
        }
    }
}
