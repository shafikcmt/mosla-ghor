<?php

namespace App\Support;

class AnnouncementUrl
{
    public static function isValid(string $url): bool
    {
        // Reject browser-normalized backslashes, control characters and network paths.
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $url)) {
            return false;
        }

        return (str_starts_with($url, '/') && ! str_starts_with($url, '//'))
            || (preg_match('~^https?://~i', $url) && filter_var($url, FILTER_VALIDATE_URL) !== false);
    }
}
