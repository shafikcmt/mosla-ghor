<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * One-image-per-request uploads for the product editor. The browser uploads each image
 * as soon as it is chosen (after resizing); we optimise it into a per-user temp folder and
 * hand back an encrypted token. The product form then submits only tokens, so request-size
 * limits (nginx/PHP) never apply to the whole form.
 *
 * Token = Crypt(json{p: path, u: user id, k: kind, e: expiry}); it cannot be forged or
 * redirected to another path without APP_KEY, and it only works for the uploading user.
 */
class TempUpload
{
    public const ROOT = 'products/tmp';
    public const TTL_SECONDS = 12 * 3600;   // token lifetime
    public const STALE_SECONDS = 24 * 3600; // temp files older than this are cleaned
    public const KINDS = ['main' => 'product', 'wholesale_main' => 'product', 'gallery' => 'product', 'variant' => 'product', 'og' => 'og'];

    /** @return array{token: string, url: string, path: string} */
    public static function store(UploadedFile $file, string $kind, int $userId): array
    {
        $profile = self::KINDS[$kind] ?? 'product';
        $path = ImageOptimizer::store($file, self::ROOT.'/'.$userId, $profile);
        if (! $path) {
            throw new \RuntimeException('Temp upload could not be written.');
        }

        return [
            'token' => Crypt::encryptString(json_encode(['p' => $path, 'u' => $userId, 'k' => $kind, 'e' => now()->timestamp + self::TTL_SECONDS])),
            'url' => ProductMedia::url('storage/'.$path),
            'path' => $path,
        ];
    }

    /**
     * The temp path for a valid token, or null (tampered, another user's, expired, wrong
     * kind, or the file is gone). Never throws.
     */
    public static function resolve(?string $token, int $userId, ?string $kind = null): ?string
    {
        if (! is_string($token) || $token === '' || strlen($token) > 2000) {
            return null;
        }
        try {
            $data = json_decode(Crypt::decryptString($token), true, 4, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return null;
        }
        $path = (string) ($data['p'] ?? '');
        $valid = ($data['u'] ?? null) === $userId
            && (int) ($data['e'] ?? 0) >= now()->timestamp
            && ($kind === null || ($data['k'] ?? null) === $kind)
            && preg_match('~^'.preg_quote(self::ROOT.'/'.$userId.'/', '~').'[A-Za-z0-9]{40}\.(webp|jpg|jpeg|png)$~D', $path)
            && Storage::disk('public')->exists($path);

        return $valid ? $path : null;
    }

    /** Preview URL for a still-valid token (used to keep uploads visible after a validation error). */
    public static function previewUrl(?string $token, int $userId, string $kind): ?string
    {
        $path = self::resolve($token, $userId, $kind);
        return $path ? ProductMedia::url('storage/'.$path) : null;
    }

    /** Delete temp uploads older than STALE_SECONDS (all users). Returns the number removed. */
    public static function cleanup(int $olderThanSeconds = self::STALE_SECONDS): int
    {
        $disk = Storage::disk('public');
        $cutoff = now()->timestamp - $olderThanSeconds;
        $removed = 0;
        try {
            foreach ($disk->allFiles(self::ROOT) as $path) {
                if ($disk->lastModified($path) < $cutoff && $disk->delete($path)) {
                    $removed++;
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Temp upload cleanup failed', ['error' => get_class($e)]);
        }
        return $removed;
    }
}
