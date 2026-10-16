<?php

namespace App\Providers;

use App\Services\MailConfigurator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Proxy trust: '*' (default) or a comma list of proxy IPs/CIDRs from TRUSTED_PROXIES.
        // Behind Cloudflare Tunnel/cloudflared the proxy is local (127.0.0.1), so narrowing it
        // to that address stops visitors from spoofing X-Forwarded-For (which feeds Meta's client_ip_address).
        $trusted = config('app.trusted_proxies', '*');
        \Illuminate\Http\Middleware\TrustProxies::at(
            in_array($trusted, ['*', '**'], true) ? $trusted : array_values(array_filter(array_map('trim', explode(',', (string) $trusted))))
        );

        // Apply admin-managed SMTP settings (DB) over .env when enabled.
        MailConfigurator::apply();

        // Bot API: per token (falls back to IP before auth resolves).
        \Illuminate\Support\Facades\RateLimiter::for('bot-api', function (\Illuminate\Http\Request $request) {
            $token = $request->attributes->get(\App\Http\Middleware\AuthenticateBotToken::ATTR);
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(120)->by($token ? 'bot:'.$token->id : 'bot-ip:'.$request->ip());
        });

        \Illuminate\Support\Facades\View::composer(
            ['home', 'storefront.*', 'partials.*', 'customer.*', 'auth.unavailable', 'faq', 'legal.*'],
            function ($view) {
                $view->with('siteName', \App\Models\WebsiteSetting::siteName());
                $view->with('siteTagline', \App\Models\WebsiteSetting::get('site_tagline', 'Authentic Spice Store'));
            }
        );
    }
}
