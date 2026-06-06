<?php

namespace App\Services\Otp;

use Illuminate\Support\Facades\Mail;

/**
 * Sends the OTP via Laravel's configured mailer. Considered configured when a
 * real transport is set (not the `log`/`array` dev mailers).
 */
class EmailOtpProvider implements OtpProvider
{
    public function isConfigured(): bool
    {
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array', null], true)) {
            return false;
        }
        // SMTP needs a host; API transports (ses/postmark/resend) are fine as-is.
        if ($mailer === 'smtp') {
            return (bool) config('mail.mailers.smtp.host');
        }

        return true;
    }

    public function send(string $to, string $code, string $message): void
    {
        Mail::raw($message, function ($mail) use ($to) {
            $mail->to($to)->subject('আপনার OTP কোড — MoslaMart');
        });
    }
}
