<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Friendly Bangla messages for uploads that failed before validation (file too big for
 * PHP, partial upload, server temp dir problems). Such files are invalid UploadedFile
 * objects; Laravel would otherwise report them as "must be an image". One message per
 * field, human field names, and a log line with the error code + limits (never contents).
 */
class UploadErrors
{
    private const BN = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    public static function bn(int|string $n): string
    {
        return strtr((string) $n, array_combine(range(0, 9), self::BN));
    }

    /** Human Bangla name for an upload field key (dot notation). */
    public static function label(string $key, array $overrides = []): string
    {
        if (isset($overrides[$key])) {
            return $overrides[$key];
        }
        return match (true) {
            $key === 'main_image_file' => 'মূল ছবি',
            $key === 'og_image_file' => 'শেয়ার ছবি',
            $key === 'video_file' => 'ভিডিও',
            (bool) preg_match('/^gallery_images\.(\d+)$/', $key, $m) => 'গ্যালারির '.self::bn((int) $m[1] + 1).' নম্বর ছবি',
            (bool) preg_match('/^(new_)?variants\.[^.]+\.image_file$/', $key) => 'ভ্যারিয়েন্টের ছবি',
            $key === 'logo' => 'লোগো',
            $key === 'banner' => 'ব্যানার',
            $key === 'payment_screenshot' => 'পেমেন্ট স্ক্রিনশট',
            $key === 'kyc_document' => 'KYC ডকুমেন্ট',
            default => 'ছবি',
        };
    }

    public static function message(UploadedFile $file, string $label, ?Request $request = null): string
    {
        $limit = ServerLimits::human(ServerLimits::uploadMax());
        $message = match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                "{$label} অনেক বড় (সার্ভারের সীমা {$limit})। ছোট ছবি দিন অথবা আবার বেছে নিন — আমরা নিজে থেকে ছোট করে দিই।",
            UPLOAD_ERR_PARTIAL =>
                "{$label} পুরোপুরি আপলোড হয়নি (সংযোগ বিচ্ছিন্ন হয়েছিল)। আবার বেছে নিয়ে সংরক্ষণ করুন।",
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION =>
                "{$label} সার্ভারে সংরক্ষণ করা যায়নি (সার্ভারের সাময়িক সমস্যা)। কিছুক্ষণ পর আবার চেষ্টা করুন।",
            default => "{$label} আপলোড হয়নি। আবার বেছে নিন।",
        };
        if ($request && self::fromOlderPage($request)) {
            $message .= ' পেজটি পুরোনো — রিফ্রেশ করে আবার চেষ্টা করুন (সাইট আপডেট হয়েছে)।';
        }
        return $message;
    }

    /** The form was rendered by an older deploy (its hidden _build differs from the running code). */
    public static function fromOlderPage(Request $request): bool
    {
        $build = (string) $request->input('_build', '');
        $current = BuildInfo::commit();
        return $build !== '' && $current !== 'unknown' && ! hash_equals($current, $build);
    }

    /**
     * Find failed uploads. Returns [dot-key => Bangla message] and logs each failure.
     *
     * @param  array<string, string>  $labels  optional label overrides by key
     */
    public static function collect(Request $request, array $labels = []): array
    {
        $errors = [];
        foreach (Arr::dot($request->allFiles()) as $key => $file) {
            if (! $file instanceof UploadedFile || $file->isValid()) {
                continue;
            }
            $errors[$key] = self::message($file, self::label($key, $labels), $request);
            Log::warning('Upload failed before validation', [
                'field' => $key,
                'error' => ServerLimits::uploadErrorName($file->getError()),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'content_length' => (int) $request->server('CONTENT_LENGTH'),
                'route' => $request->route()?->getName(),
                'user_id' => $request->user()?->id,
            ]);
        }
        return $errors;
    }

    /** For single-field forms: throw a normal validation error for failed uploads. */
    public static function guard(Request $request, array $labels = []): void
    {
        $errors = self::collect($request, $labels);
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * Bangla messages for image rules on the given attribute keys (wildcards allowed).
     * Use with the 'bail' rule so a field shows only its first problem.
     */
    public static function imageMessages(array $keys, int $maxMb = 10): array
    {
        $messages = [];
        foreach ($keys as $key) {
            $messages += [
                "{$key}.uploaded"   => ':attribute আপলোড হয়নি। আবার বেছে নিন।',
                "{$key}.file"       => ':attribute একটি ফাইল হতে হবে।',
                "{$key}.image"      => ':attribute একটি ছবি হতে হবে (JPG, PNG বা WebP)।',
                "{$key}.mimes"      => ':attribute শুধু JPG, PNG বা WebP হতে পারবে।',
                "{$key}.extensions" => ':attribute শুধু JPG, PNG বা WebP হতে পারবে।',
                "{$key}.max"        => ':attribute সর্বোচ্চ '.self::bn($maxMb).' MB হতে পারবে।',
                "{$key}.required"   => ':attribute দিন।',
            ];
        }
        return $messages;
    }
}
