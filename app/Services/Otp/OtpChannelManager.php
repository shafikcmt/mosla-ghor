<?php

namespace App\Services\Otp;

/**
 * Resolves the right provider for an OTP channel, with a safe dev fallback.
 *
 * - If the requested channel's provider is configured → use it.
 * - Else, in local / APP_DEBUG → fall back to {@see LogOtpProvider} so a
 *   developer can read the code from laravel.log.
 * - Else (production, nothing configured) → throw {@see OtpUnavailableException}
 *   carrying a friendly Bangla message.
 */
class OtpChannelManager
{
    public function for(string $channel): OtpProvider
    {
        $provider = $this->provider($channel);

        if ($provider->isConfigured()) {
            return $provider;
        }

        if (app()->environment('local') || config('app.debug')) {
            return new LogOtpProvider();
        }

        throw new OtpUnavailableException();
    }

    private function provider(string $channel): OtpProvider
    {
        return match ($channel) {
            'sms'      => new SmsOtpProvider(),
            'whatsapp' => new WhatsappOtpProvider(),
            'email'    => new EmailOtpProvider(),
            default    => new LogOtpProvider(),
        };
    }
}
