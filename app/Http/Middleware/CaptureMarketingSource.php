<?php

namespace App\Http\Middleware;

use App\Support\MarketingSource;
use App\Support\MetaPixel;
use App\Support\VisitLogger;
use Illuminate\Support\Str;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Remembers the last UTM-tagged landing in a first-party cookie (`ms_attr`, 30 days)
 * so orders and enquiries can be attributed to their marketing source.
 * No fbclid / IP / contact data is stored. Failures are silent.
 */
class CaptureMarketingSource
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if (! self::shouldTrack($request)) {
                return $response;
            }
            $utm = MarketingSource::utmFromQuery($request->query());
            if (! $utm) {
                return $response;
            }

            $referrer = MarketingSource::cleanHost(parse_url((string) $request->headers->get('referer'), PHP_URL_HOST) ?: null);
            if ($referrer === MarketingSource::cleanHost($request->getHost())) {
                $referrer = null;
            }

            $payload = $utm + [
                'landing_path'  => MarketingSource::cleanPath($request->getPathInfo()) ?? '/',
                'referrer_host' => $referrer,
                'captured_at'   => now()->toIso8601String(),
            ];

            $response->headers->setCookie(new Cookie(
                MarketingSource::COOKIE,
                json_encode($payload, JSON_UNESCAPED_UNICODE),
                now()->addDays(30),
                '/',
                null,
                $request->isSecure(),
                true,
                false,
                Cookie::SAMESITE_LAX,
            ));
        } catch (\Throwable $e) {
            // Tracking must never break a page.
        }

        // Anonymous visit logging is independent: a failure here must not drop the attribution cookie.
        try {
            if (isset($utm, $payload)) {
                $this->prepareVisit($request, $response, $payload);
            }
        } catch (\Throwable $e) {
            // fail silently
        }

        return $response;
    }

    /** Runs after the response has been sent; inserts the row stashed by prepareVisit(). */
    public function terminate(Request $request, Response $response): void
    {
        try {
            $row = $request->attributes->get('ms_visit');
            if (is_array($row)) {
                VisitLogger::record($row);
            }
        } catch (\Throwable $e) {
            // A logging failure can never break a page.
        }
    }

    private function prepareVisit(Request $request, Response $response, array $payload): void
    {
        $ua = (string) $request->userAgent();
        if (! VisitLogger::enabled() || VisitLogger::isBot($ua)) {
            return;
        }
        // Same exclusions as the Meta Pixel: META_EXCLUDE_IPS and logged-in admins.
        if (! MetaPixel::trackingAllowed()) {
            return;
        }

        $visitorId = $request->cookie(VisitLogger::COOKIE);
        if (! VisitLogger::validVisitorId($visitorId)) {
            $visitorId = (string) Str::uuid();
            $response->headers->setCookie(new Cookie(
                VisitLogger::COOKIE, $visitorId, now()->addDays(180), '/', null,
                $request->isSecure(), true, false, Cookie::SAMESITE_LAX,
            ));
        }

        $request->attributes->set('ms_visit', [
            'visitor_id'    => strtolower($visitorId),
            'utm_source'    => $payload['utm_source'] ?? null,
            'utm_medium'    => $payload['utm_medium'] ?? null,
            'utm_campaign'  => $payload['utm_campaign'] ?? null,
            'utm_content'   => $payload['utm_content'] ?? null,
            'utm_term'      => $payload['utm_term'] ?? null,
            'landing_path'  => $payload['landing_path'],
            'referrer_host' => $payload['referrer_host'] ?? null,
            'device'        => VisitLogger::device($ua),
        ]);
    }

    /** GET, non-AJAX, public storefront pages only. */
    public static function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }
        $path = trim($request->getPathInfo(), '/');
        if (preg_match('#^(admin|vendor|api|feeds|storage|build|icons|images|livewire|invoice|wholesale-invoice|v|order|checkout|account|set-password|track-order)(/|$)#i', $path)) {
            return false;
        }
        if (in_array($path, ['sitemap.xml', 'robots.txt', 'up'], true)) {
            return false;
        }
        // asset-like paths (file extension on the last segment)
        if (preg_match('/\.[A-Za-z0-9]{2,5}$/', $path)) {
            return false;
        }

        return true;
    }
}
