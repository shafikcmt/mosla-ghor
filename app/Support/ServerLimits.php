<?php

namespace App\Support;

use App\Models\WebsiteSetting;

/** Live PHP/server upload limits, readable without shell access (admin diagnostics + client guard). */
class ServerLimits
{
    public const MEASURED_KEY = 'upload_measured_limit_bytes';
    public const MEASURED_AT_KEY = 'upload_measured_at';

    public static function bytes(string $iniKey): int
    {
        return self::toBytes((string) ini_get($iniKey));
    }

    public static function toBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1; // unlimited
        }
        $n = (float) $value;
        return (int) match (strtolower(substr($value, -1))) {
            'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
        };
    }

    public static function uploadMax(): int { return self::bytes('upload_max_filesize'); }
    public static function postMax(): int { return self::bytes('post_max_size'); }

    /** Largest request size the admin's upload test got through (nginx + PHP), or null. */
    public static function measured(): ?int
    {
        $v = (int) WebsiteSetting::get(self::MEASURED_KEY, '0');
        return $v > 0 ? $v : null;
    }

    /**
     * Safe total request size for the client-side guard: the smallest known limit
     * (PHP post_max_size, and the measured nginx/PHP limit when the test has been run).
     */
    public static function safeRequestBytes(): int
    {
        $limits = array_filter([self::postMax(), self::measured()], fn ($v) => $v !== null && $v > 0);
        return $limits ? (int) min($limits) : 8 * 1024 * 1024;
    }

    /** Safe single-file size: PHP upload_max_filesize (and never above the request limit). */
    public static function safeFileBytes(): int
    {
        $limits = array_filter([self::uploadMax(), self::safeRequestBytes()], fn ($v) => $v > 0);
        return $limits ? (int) min($limits) : 2 * 1024 * 1024;
    }

    public static function tempDir(): string
    {
        return (string) (ini_get('upload_tmp_dir') ?: sys_get_temp_dir());
    }

    public static function tempDirWritable(): bool
    {
        return is_dir(self::tempDir()) && is_writable(self::tempDir());
    }

    public static function freeDiskBytes(): ?int
    {
        $free = @disk_free_space(storage_path());
        return $free === false ? null : (int) $free;
    }

    /** "2 MB", "512 KB", "সীমাহীন". */
    public static function human(?int $bytes): string
    {
        if ($bytes === null) {
            return '—';
        }
        if ($bytes < 0) {
            return 'সীমাহীন';
        }
        if ($bytes >= 1024 ** 3) {
            return round($bytes / 1024 ** 3, 1).' GB';
        }
        if ($bytes >= 1024 ** 2) {
            return rtrim(rtrim(number_format($bytes / 1024 ** 2, 1), '0'), '.').' MB';
        }
        return round($bytes / 1024).' KB';
    }

    public static function uploadErrorName(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE   => 'UPLOAD_ERR_INI_SIZE',
            UPLOAD_ERR_FORM_SIZE  => 'UPLOAD_ERR_FORM_SIZE',
            UPLOAD_ERR_PARTIAL    => 'UPLOAD_ERR_PARTIAL',
            UPLOAD_ERR_NO_FILE    => 'UPLOAD_ERR_NO_FILE',
            UPLOAD_ERR_NO_TMP_DIR => 'UPLOAD_ERR_NO_TMP_DIR',
            UPLOAD_ERR_CANT_WRITE => 'UPLOAD_ERR_CANT_WRITE',
            UPLOAD_ERR_EXTENSION  => 'UPLOAD_ERR_EXTENSION',
            default               => 'UPLOAD_OK',
        };
    }
}
