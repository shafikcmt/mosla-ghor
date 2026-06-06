<?php

namespace App\Services\Otp;

use RuntimeException;

/**
 * Thrown by {@see OtpService::issue()} when a fresh code is requested while the
 * resend cooldown is still running. Carries the remaining wait in seconds and a
 * friendly Bangla message safe to show the end user.
 */
class OtpThrottleException extends RuntimeException
{
    public function __construct(public readonly int $secondsRemaining)
    {
        parent::__construct("অনুগ্রহ করে {$secondsRemaining} সেকেন্ড পর আবার OTP চান।");
    }
}
