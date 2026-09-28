<?php

namespace App\Http\Middleware;

use App\Support\Maintenance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Admin-controlled maintenance (WebsiteSetting flags), not `php artisan down`,
 * so the admin panel keeps working. Path rules live in config/maintenance.php.
 *
 * Safety: /admin is never blocked (hard-coded, independent of config); a missing
 * config (e.g. a stale config:cache on deploy) falls back to DEFAULT_RULES; any
 * error while deciding fails OPEN (request passes) and is logged.
 */
class MaintenanceMode
{
    /** Fallback when config('maintenance') is missing. Keep in sync with config/maintenance.php. */
    public const DEFAULT_RULES = [
        'always_allowed' => ['admin', 'admin/*', 'up', 'storage/*', 'build/*', 'css/*', 'js/*', 'images/*', 'icons/*',
            'manifest.json', 'sw.js', 'favicon.ico', 'robots.txt'],
        'vendor' => ['vendor', 'vendor/*'],
        'vendor_always_allowed' => ['vendor/login', 'vendor/login/*', 'vendor/logout'],
        'full_read_only' => ['GET' => ['track-order', 'invoice/*', 'wholesale-invoice/*'], 'POST' => ['track-order', 'logout']],
        'full_read_only_except' => ['invoice/*/pay', 'invoice/*/reorder'],
        'order_paths' => ['checkout', 'checkout/*', 'order', 'invoice/*/reorder'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // The admin panel must always work — it is where maintenance is switched off.
        if ($request->is('admin', 'admin/*')) {
            return $next($request);
        }

        try {
            $blocked = $this->decide($request);
        } catch (\Throwable $e) {
            // Fail open: a broken maintenance check must never take the site down.
            Log::error('Maintenance check failed; request allowed', [
                'path' => $request->path(), 'error' => get_class($e).': '.$e->getMessage(),
            ]);
            $blocked = null;
        }

        return $blocked ?? $next($request);
    }

    /** null = let the request through; a Response = blocked. */
    private function decide(Request $request): ?Response
    {
        // Off → exactly today's behaviour.
        if (! Maintenance::enabled() || $request->is(...$this->rule('always_allowed'))) {
            return null;
        }

        // Admins browse the real site (with the admin bar); allowed IPs bypass silently.
        if ($request->user()?->is_admin || Maintenance::ipAllowed($request)) {
            return null;
        }

        if ($request->is(...$this->rule('vendor'))) {
            if (! Maintenance::blocksVendors() || $request->is(...$this->rule('vendor_always_allowed'))) {
                return null;
            }
            return $this->block($request, 'maintenance.vendor-notice');
        }

        if (Maintenance::isFull()) {
            $readOnlyRules = $this->rule('full_read_only');
            $readOnly = $readOnlyRules[$request->method()] ?? ($request->isMethod('HEAD') ? ($readOnlyRules['GET'] ?? []) : []);
            if ($readOnly && $request->is(...$readOnly) && ! $request->is(...$this->rule('full_read_only_except'))) {
                return null;
            }
            return $this->block($request, 'maintenance.notice');
        }

        // Banner mode: the site works; only order placement can be stopped.
        if (Maintenance::blocksOrders() && $request->is(...$this->rule('order_paths'))) {
            return $this->block($request, 'maintenance.orders-closed', 'অর্ডার সাময়িকভাবে বন্ধ আছে। ');
        }

        return null;
    }

    private function rule(string $key): array
    {
        $config = config('maintenance');
        $rules = is_array($config) ? $config : self::DEFAULT_RULES;

        return $rules[$key] ?? self::DEFAULT_RULES[$key];
    }

    private function block(Request $request, string $view, string $prefix = ''): Response
    {
        $headers = ['Retry-After' => (string) Maintenance::retryAfter()];

        if ($request->expectsJson()) {
            return response()->json(['message' => $prefix.Maintenance::message(), 'maintenance' => true], 503, $headers);
        }

        return response()->view($view, [], 503, $headers);
    }
}
