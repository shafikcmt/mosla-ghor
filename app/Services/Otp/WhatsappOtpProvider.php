<?php

namespace App\Services\Otp;

use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * WhatsApp delivery via an HTTP API (e.g. WhatsApp Cloud API). Configured once
 * WHATSAPP_API_URL + WHATSAPP_API_TOKEN are set in .env. Payload shape varies
 * by provider; adjust to match yours.
 */
class WhatsappOtpProvider implements OtpProvider
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.whatsapp.endpoint')) && ! empty(config('services.whatsapp.token'));
    }

    public function send(string $to, string $code, string $message): void
    {
        $wa = Phone::toWa($to) ?? $to;

        $response = Http::withToken(config('services.whatsapp.token'))->post(config('services.whatsapp.endpoint'), [
            'messaging_product' => 'whatsapp',
            'to'                => $wa,
            'type'              => 'text',
            'text'              => ['body' => $message],
        ]);

        if ($response->failed()) {
            Log::warning('[OTP][WhatsApp] gateway error', ['to' => $wa, 'status' => $response->status()]);
            throw new OtpUnavailableException('WhatsApp OTP পাঠাতে সমস্যা হয়েছে। একটু পরে আবার চেষ্টা করুন।');
        }
    }
}
