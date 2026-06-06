<?php

namespace App\Services\Otp;

use RuntimeException;

/**
 * Thrown when an OTP channel has no provider configured and we are not in a
 * dev environment that can fall back to the log channel. Carries a friendly
 * Bangla message safe to show the end user.
 */
class OtpUnavailableException extends RuntimeException
{
    public function __construct(string $message = 'OTP service এখনো configure করা হয়নি। অনুগ্রহ করে পাসওয়ার্ড দিয়ে লগইন করুন।')
    {
        parent::__construct($message);
    }
}
