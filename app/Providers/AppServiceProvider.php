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
        // Apply admin-managed SMTP settings (DB) over .env when enabled.
        MailConfigurator::apply();

        \Illuminate\Support\Facades\View::composer(
            ['home', 'storefront.*', 'partials.*', 'customer.*', 'auth.unavailable', 'faq'],
            function ($view) {
                $view->with('siteName', \App\Models\WebsiteSetting::siteName());
                $view->with('siteTagline', \App\Models\WebsiteSetting::get('site_tagline', 'Authentic Spice Store'));
            }
        );
    }
}
