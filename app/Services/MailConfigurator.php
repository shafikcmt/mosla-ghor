<?php

namespace App\Services;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Applies admin-managed SMTP settings (from the mail_settings table) to the
 * runtime mail config, overriding whatever .env provides. When no usable DB
 * settings exist, the .env configuration (default: log driver) is left intact,
 * so existing behaviour never breaks.
 *
 * Mirrors the "settings stored in DB, applied at runtime" idea behind the
 * courier API settings — here applied to Laravel's mail config.
 */
class MailConfigurator
{
    public static function apply(): void
    {
        // Table may not exist yet (fresh checkout before migration) — fail silent.
        try {
            if (! Schema::hasTable('mail_settings')) {
                return;
            }

            $settings = MailSetting::query()->first();
        } catch (\Throwable) {
            return;
        }

        if (! $settings || ! $settings->isUsable()) {
            return; // fall back to .env
        }

        // ssl → implicit TLS (smtps, port 465); tls/null → STARTTLS (smtp).
        $scheme = $settings->encryption === 'ssl' ? 'smtps' : 'smtp';

        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp', array_merge(
            (array) Config::get('mail.mailers.smtp', []),
            [
                'transport'  => 'smtp',
                'scheme'     => $scheme,
                'host'       => $settings->host,
                'port'       => $settings->port,
                'username'   => $settings->username,
                'password'   => $settings->password,
                'encryption' => $settings->encryption, // back-compat for older transports
            ]
        ));

        if (! empty($settings->from_address)) {
            Config::set('mail.from.address', $settings->from_address);
        }
        if (! empty($settings->from_name)) {
            Config::set('mail.from.name', $settings->from_name);
        }
    }
}
