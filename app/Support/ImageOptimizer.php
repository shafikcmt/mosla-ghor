<?php

namespace App\Support;

use App\Models\WebsiteSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\ImageManager;

/**
 * Server-side image optimisation for every image upload (the browser resizes first;
 * see public/js/image-resize.js). Works on today's live server: GD without WebP,
 * small PHP limits. Nothing here may break an upload — on any problem the original
 * file is stored exactly as before.
 *
 * Decision per upload:
 *  - profile 'kyc' / disabled / not an image          → store as-is (no log)
 *  - fits the profile, no EXIF/XMP, GD can't decode it → store as-is (no log)
 *  - otherwise re-encode (auto-orient, scale down only, all metadata dropped by GD)
 *    to WebP; without WebP → JPEG, or PNG when the source has transparency;
 *    og_image → JPEG cover-cropped to 1200×630.
 *  - result bigger than the original and the original has no metadata → keep original.
 */
class ImageOptimizer
{
    public const MAX_PIXELS = 40_000_000; // ~40 MP decompression-bomb / memory guard

    /** Tests can simulate a GD build with/without WebP, or a deploy without the library. */
    public static ?bool $forceWebp = null;
    public static ?bool $forceLibrary = null;

    /** intervention/image present (i.e. the deploy ran composer install). */
    public static function libraryInstalled(): bool
    {
        return self::$forceLibrary ?? class_exists(ImageManager::class);
    }

    /** @return array{max: int, quality: int, format: string, crop: ?array}|null */
    public static function profile(string $name): ?array
    {
        $q = self::settingInt('image_quality', 80, 40, 95);
        $productMax = self::settingInt('image_max_product_px', 1600, 600, 4000);

        return match ($name) {
            'product' => ['max' => $productMax, 'quality' => $q, 'format' => 'webp', 'crop' => null],
            'og'      => ['max' => 1200, 'quality' => 82, 'format' => 'jpeg', 'crop' => [1200, 630]],
            'hero'    => ['max' => 1920, 'quality' => $q, 'format' => 'webp', 'crop' => null],
            'logo'    => ['max' => 512, 'quality' => 85, 'format' => 'webp', 'crop' => null],
            'banner'  => ['max' => 1600, 'quality' => $q, 'format' => 'webp', 'crop' => null],
            'review', 'return' => ['max' => 1280, 'quality' => 78, 'format' => 'webp', 'crop' => null],
            'payment' => ['max' => 2000, 'quality' => 85, 'format' => 'webp', 'crop' => null],
            default   => null, // includes 'kyc': never processed
        };
    }

    public static function enabled(): bool
    {
        return WebsiteSetting::get('image_optimize_enabled', '1') === '1';
    }

    /**
     * Store an upload like UploadedFile::store() does and return the same kind of
     * relative path (e.g. "products/images/abc.webp"). Throws only if the disk write fails.
     */
    public static function store(UploadedFile $file, string $folder, ?string $profileName, string $disk = 'public'): string|false
    {
        $profile = $profileName ? self::profile($profileName) : null;
        if (! $profile || ! self::enabled()) {
            return $file->store($folder, $disk);
        }

        $result = self::optimize($file->getRealPath(), $profile, $profileName);
        if ($result === null) {
            return $file->store($folder, $disk);
        }

        $path = trim($folder, '/').'/'.Str::random(40).'.'.$result['ext'];
        return Storage::disk($disk)->put($path, $result['bytes']) ? $path : false;
    }

    /**
     * Returns ['bytes' => …, 'ext' => …] for a new encoding, or null = keep the original.
     * Never throws. $sameFormat is used by images:optimize-existing (no path/extension change).
     */
    public static function optimize(string $path, array $profile, string $profileName = '', bool $sameFormat = false): ?array
    {
        $size = @filesize($path) ?: 0;
        $info = @getimagesize($path);
        if (! $info || $size === 0) {
            self::warn('unreadable image', $profileName, $size);
            return null;
        }
        [$w, $h, $type] = [(int) $info[0], (int) $info[1], (int) $info[2]];

        $hasMeta = self::hasMetadata($path);
        $fits = $profile['crop']
            ? ($w === $profile['crop'][0] && $h === $profile['crop'][1] && $type === IMAGETYPE_JPEG)
            : max($w, $h) <= $profile['max'];
        $decodable = self::canDecode($type);

        if (! $decodable) {
            // Already fine and nothing to strip → store silently (e.g. WebP on a GD without WebP).
            if (! ($fits && ! $hasMeta)) {
                self::warn('format not decodable by GD', $profileName, $size);
            }
            return null;
        }
        if (! self::libraryInstalled()) {
            // Deploy without `composer install`: never break the upload.
            self::warn('image library not installed', $profileName, $size);
            return null;
        }
        if ($w * $h > self::MAX_PIXELS || ! self::memoryAllows($w, $h)) {
            self::warn('image too large for available memory', $profileName, $size);
            return null;
        }

        try {
            $format = self::outputFormat($type, $profile, $path, $sameFormat);
            $manager = new ImageManager(new GdDriver()); // reads EXIF orientation and applies it
            $image = $manager->read($path);

            if ($profile['crop'] && ! $sameFormat) {
                $image->cover($profile['crop'][0], $profile['crop'][1]);
            } else {
                $image->scaleDown(width: $profile['max'], height: $profile['max']); // never upscales
            }

            $bytes = (string) match ($format) {
                'webp' => $image->toWebp(quality: $profile['quality']),
                'png'  => $image->toPng(),
                default => $image->toJpeg(quality: $profile['quality']),
            };
        } catch (\Throwable $e) {
            self::warn('encode failed: '.get_class($e), $profileName, $size);
            return null;
        }

        // GD never writes EXIF/GPS/XMP, so the new bytes are always metadata-free.
        if ($bytes === '' || (! $hasMeta && strlen($bytes) >= $size)) {
            return null; // no gain and nothing private to strip → keep the original
        }

        return ['bytes' => $bytes, 'ext' => $format === 'jpeg' ? 'jpg' : $format];
    }

    public static function webpSupported(): bool
    {
        if (self::$forceWebp !== null) {
            return self::$forceWebp;
        }
        static $detected = null;
        return $detected ??= (bool) Cache::remember('image_optimizer.webp_support', 86400, fn () => function_exists('imagewebp')
            && function_exists('imagecreatefromwebp')
            && (bool) (function_exists('gd_info') ? (gd_info()['WebP Support'] ?? false) : false));
    }

    /** Attributes for <input type="file"> so the browser resizes with the same profile. */
    public static function inputAttributes(string $profileName): HtmlString
    {
        $p = self::profile($profileName);
        if (! $p || ! self::enabled()) {
            return new HtmlString('');
        }
        $attrs = sprintf('data-resize="%s" data-resize-max="%d" data-resize-quality="%.2f" data-resize-format="%s"',
            e($profileName), $p['max'], $p['quality'] / 100, $p['format']);
        if ($p['crop']) {
            $attrs .= sprintf(' data-resize-crop="%dx%d"', $p['crop'][0], $p['crop'][1]);
        }
        return new HtmlString($attrs);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    private static function outputFormat(int $type, array $profile, string $path, bool $sameFormat): string
    {
        if ($sameFormat) {
            return match ($type) { IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', default => 'jpeg' };
        }
        if ($profile['format'] === 'jpeg') {
            return 'jpeg';
        }
        if (self::webpSupported()) {
            return 'webp';
        }
        return self::hasAlpha($path, $type) ? 'png' : 'jpeg';
    }

    private static function canDecode(int $type): bool
    {
        return match ($type) {
            IMAGETYPE_JPEG => function_exists('imagecreatefromjpeg'),
            IMAGETYPE_PNG  => function_exists('imagecreatefrompng'),
            IMAGETYPE_GIF  => function_exists('imagecreatefromgif'),
            IMAGETYPE_WEBP => self::webpSupported(),
            default        => false,
        };
    }

    /** EXIF / XMP / PNG eXIf blocks (where GPS lives) in the first 256 KB. */
    public static function hasMetadata(string $path): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 262144);
        foreach (["Exif\0\0", 'eXIf', 'http://ns.adobe.com/xap/1.0/', 'XML:com.adobe.xmp'] as $marker) {
            if (str_contains($head, $marker)) {
                return true;
            }
        }
        // WebP RIFF chunks
        return str_starts_with($head, 'RIFF') && (str_contains($head, 'EXIF') || str_contains($head, 'XMP '));
    }

    private static function hasAlpha(string $path, int $type): bool
    {
        $head = (string) @file_get_contents($path, false, null, 0, 65536);
        if ($type === IMAGETYPE_PNG) {
            $colorType = ord($head[25] ?? "\0");
            return in_array($colorType, [4, 6], true) || str_contains($head, 'tRNS');
        }
        if ($type === IMAGETYPE_WEBP) {
            return str_contains($head, 'VP8L') || (str_contains($head, 'VP8X') && (ord($head[20] ?? "\0") & 0x10));
        }
        return $type === IMAGETYPE_GIF;
    }

    private static function memoryAllows(int $w, int $h): bool
    {
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true; // unlimited
        }
        $needed = $w * $h * 5 * 2; // decoded RGBA + a scaled copy, with overhead
        return $needed < ($limit - memory_get_usage(true)) * 0.8;
    }

    private static function bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }
        $n = (int) $value;
        return match (strtolower(substr($value, -1))) {
            'g' => $n * 1024 ** 3, 'm' => $n * 1024 ** 2, 'k' => $n * 1024, default => $n,
        };
    }

    private static function settingInt(string $key, int $default, int $min, int $max): int
    {
        try {
            $v = (int) WebsiteSetting::get($key, (string) $default);
        } catch (\Throwable) {
            $v = $default;
        }
        return max($min, min($max, $v ?: $default));
    }

    /** Never logs file contents or the uploader's file name. */
    private static function warn(string $reason, string $profile, int $bytes): void
    {
        Log::warning('Image optimization skipped; original stored', ['reason' => $reason, 'profile' => $profile, 'bytes' => $bytes]);
    }
}
