<?php

namespace App\Services\Otp;

interface OtpProvider
{
    /** True when this channel has real credentials and can deliver. */
    public function isConfigured(): bool;

    /**
     * Deliver the code to the recipient.
     *
     * @param  string  $to       normalized phone or email
     * @param  string  $code      the plain OTP (only used to build the message)
     * @param  string  $message   ready-to-send Bangla message body
     */
    public function send(string $to, string $code, string $message): void;
}
