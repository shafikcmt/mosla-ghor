<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generic HTTP SMS gateway. Configured once SMS_API_URL + SMS_API_KEY are set
 * in .env (see config/services.php → sms). The exact payload differs per
 * Bangladeshi gateway; adjust the body keys to match your provider.
 */
class SmsOtpProvider implements OtpProvider
{
    public function isConfigured(): bool
    {
        return ! empty(config('services.sms.endpoint')) && ! empty(config('services.sms.key'));
    }

    public function send(string $to, string $code, string $message): void
    {
        $response = Http::asForm()->post(config('services.sms.endpoint'), [
            'api_key' => config('services.sms.key'),
            'sender'  => config('services.sms.sender'),
            'to'      => $to,
            'message' => $message,
        ]);

        if ($response->failed()) {
            Log::warning('[OTP][SMS] gateway error', ['to' => $to, 'status' => $response->status()]);
            throw new OtpUnavailableException('SMS পাঠাতে সমস্যা হয়েছে। একটু পরে আবার চেষ্টা করুন।');
        }
    }
}
