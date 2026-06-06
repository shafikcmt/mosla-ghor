<?php

namespace App\Services\Otp;

use App\Models\AuthOtp;
use App\Support\AuthSettings;
use App\Support\Phone;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Central glue for one-time-code auth. Ties together {@see AuthOtp} storage,
 * the admin-controlled {@see AuthSettings} (expiry / cooldown / attempts) and
 * the {@see OtpChannelManager} delivery providers.
 *
 * Codes are stored hashed (never plain). The plain code only ever lives long
 * enough to build the outgoing message. All public methods normalise the
 * identifier the same way so issue + verify always agree on the key.
 */
class OtpService
{
    public function __construct(private OtpChannelManager $channels)
    {
    }

    /**
     * Generate, persist (hashed) and dispatch a fresh code.
     *
     * @param  string  $identifier  raw phone or email (normalised internally)
     * @param  string  $purpose     login | register | verify_phone | ...
     * @param  string  $channel     sms | whatsapp | email
     * @param  string|null  $userType  customer | vendor | admin
     * @param  array{ip?:string,user_agent?:string}  $meta
     *
     * @throws OtpThrottleException     when still inside the resend cooldown
     * @throws OtpUnavailableException  when the channel has no usable provider
     */
    public function issue(string $identifier, string $purpose, string $channel, ?string $userType = null, array $meta = []): AuthOtp
    {
        [$id, $type] = $this->normalize($identifier, $channel);

        $wait = $this->cooldownRemaining($id, $purpose);
        if ($wait > 0) {
            throw new OtpThrottleException($wait);
        }

        // Resolve the provider up front so a misconfigured channel fails before
        // we persist a code the user can never receive.
        $provider = $this->channels->for($channel);

        $code = $this->generateCode();

        $otp = AuthOtp::create([
            'identifier'      => $id,
            'identifier_type' => $type,
            'channel'         => $channel,
            'otp_hash'        => Hash::make($code),
            'purpose'         => $purpose,
            'user_type'       => $userType,
            'expires_at'      => Carbon::now()->addMinutes(AuthSettings::otpExpiryMinutes()),
            'attempts'        => 0,
            'max_attempts'    => AuthSettings::otpMaxAttempts(),
            'ip_address'      => $meta['ip'] ?? null,
            'user_agent'      => $meta['user_agent'] ?? null,
        ]);

        $provider->send($id, $code, $this->message($code));

        return $otp;
    }

    /**
     * Check a user-supplied code against the newest unverified code for this
     * identifier + purpose. A correct code is single-use (marks verified_at);
     * a wrong code burns one attempt and locks once max_attempts is reached.
     */
    public function verify(string $identifier, string $purpose, string $code): bool
    {
        [$id] = $this->normalize($identifier, $this->channelHint($identifier));

        /** @var AuthOtp|null $otp */
        $otp = AuthOtp::query()->latestFor($id, $purpose)->first();

        if (! $otp || $otp->isExpired() || $otp->isLocked()) {
            return false;
        }

        $otp->increment('attempts');

        if (! Hash::check($code, $otp->otp_hash)) {
            return false;
        }

        $otp->forceFill(['verified_at' => Carbon::now()])->save();

        return true;
    }

    /** Seconds the caller must still wait before a fresh code may be issued. */
    public function cooldownRemaining(string $identifier, string $purpose): int
    {
        $cooldown = AuthSettings::otpResendCooldownSeconds();
        if ($cooldown <= 0) {
            return 0;
        }

        [$id] = $this->normalize($identifier, $this->channelHint($identifier));

        $last = AuthOtp::query()
            ->where('identifier', $id)
            ->where('purpose', $purpose)
            ->latest()
            ->first();

        if (! $last) {
            return 0;
        }

        $elapsed = $last->created_at->diffInSeconds(Carbon::now());

        return (int) max(0, $cooldown - $elapsed);
    }

    /** Normalise an identifier to its canonical stored form + its type. */
    private function normalize(string $identifier, string $channel): array
    {
        if ($channel === 'email' || str_contains($identifier, '@')) {
            return [mb_strtolower(trim($identifier)), 'email'];
        }

        return [Phone::normalize($identifier) ?? trim($identifier), 'phone'];
    }

    /** Best-effort channel guess from the identifier shape (verify/cooldown). */
    private function channelHint(string $identifier): string
    {
        return str_contains($identifier, '@') ? 'email' : 'sms';
    }

    /** Six-digit numeric code, zero-padded. */
    private function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function message(string $code): string
    {
        $minutes = AuthSettings::otpExpiryMinutes();

        return "আপনার MoslaMart OTP কোড: {$code}\nএই কোডটি {$minutes} মিনিট পর্যন্ত বৈধ। কোডটি কারও সাথে শেয়ার করবেন না।";
    }
}
