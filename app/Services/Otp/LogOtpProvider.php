<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Log;

/**
 * Development fallback — never delivers anything externally, just records the
 * code in laravel.log so a developer can read it. Always "configured".
 */
class LogOtpProvider implements OtpProvider
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function send(string $to, string $code, string $message): void
    {
        Log::channel(config('logging.default'))->info("[OTP] {$to} => {$code}");
    }
}
