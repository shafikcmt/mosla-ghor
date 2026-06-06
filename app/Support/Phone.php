<?php

namespace App\Support;

/**
 * Bangladeshi phone helpers. Canonical stored form is the local `01XXXXXXXXX`.
 * Used by auth (login/OTP) and the WhatsApp share flow so numbers normalise
 * consistently everywhere.
 */
class Phone
{
    /** Bengali → English digits. */
    private const BN_DIGITS = ['০' => '0', '১' => '1', '২' => '2', '৩' => '3', '৪' => '4', '৫' => '5', '৬' => '6', '৭' => '7', '৮' => '8', '৯' => '9'];

    /**
     * Normalise to canonical local form `01XXXXXXXXX`, or return null if it
     * cannot be coerced into a plausible BD mobile number.
     */
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        // Bengali digits → ASCII, then strip everything except digits and a leading +.
        $s = strtr(trim($raw), self::BN_DIGITS);
        $s = preg_replace('/[^\d+]/', '', $s);
        if ($s === '' || $s === null) {
            return null;
        }

        // +8801…/8801… → 01…
        if (str_starts_with($s, '+880')) {
            $s = '0' . substr($s, 4);
        } elseif (str_starts_with($s, '880')) {
            $s = '0' . substr($s, 3);
        } elseif (str_starts_with($s, '1') && strlen($s) === 10) {
            // 1XXXXXXXXX (missing leading 0)
            $s = '0' . $s;
        }

        return $s;
    }

    public static function isValidBd(?string $raw): bool
    {
        $n = self::normalize($raw);

        return $n !== null && preg_match('/^01[3-9]\d{8}$/', $n) === 1;
    }

    /** Canonical international form for wa.me (8801XXXXXXXXX). */
    public static function toWa(?string $raw): ?string
    {
        $n = self::normalize($raw);
        if ($n === null) {
            return null;
        }

        return str_starts_with($n, '0') ? '88' . $n : $n;
    }
}
