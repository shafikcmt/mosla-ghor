<?php

namespace App\Http\Middleware;

use App\Support\MarketingSource;
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

        return $response;
    }

    /** GET, non-AJAX, public storefront pages only. */
    public static function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->ajax() || $request->expectsJson()) {
            return false;
        }
        $path = trim($request->getPathInfo(), '/');
        if (preg_match('#^(admin|vendor|api|feeds|storage|build|icons|images|livewire)(/|$)#i', $path)) {
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
