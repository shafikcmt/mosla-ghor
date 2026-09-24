<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/** One save's staged uploads. Old files are removed only after the DB commits. */
class ProductMedia
{
    private array $created = [];
    private array $obsolete = [];

    public static function url(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (preg_match('~^https?://~i', $path)) {
            return $path;
        }
        $path = ltrim(str_replace('\\', '/', $path), '/');
        if (str_starts_with($path, 'products/')) {
            $path = 'storage/' . $path;
        }
        return asset($path);
    }

    public function store(UploadedFile $file, string $folder, string $field): string
    {
        try {
            $path = $file->store($folder, 'public');
        } catch (\Throwable $e) {
            report($e);
            $path = false;
        }
        if (! $path) {
            throw ValidationException::withMessages([$field => 'ফাইল সংরক্ষণ করা যায়নি। আগের ছবি অক্ষত আছে; আবার চেষ্টা করুন।']);
        }
        $this->created[] = $path;
        return 'storage/' . $path;
    }

    public function retire(?string $path): void
    {
        if ($path) {
            $this->obsolete[] = $path;
        }
    }

    public function rollback(): void
    {
        foreach ($this->created as $path) {
            $this->delete($path);
        }
    }

    public function committed(): void
    {
        foreach (array_unique($this->obsolete) as $storedPath) {
            $path = self::localPath($storedPath);
            if (! $path) {
                continue;
            }
            // Legacy shared images may be referenced by more than one product.
            $aliases = array_unique([$storedPath, $path, 'storage/'.$path, '/storage/'.$path, str_replace('/', '\\', 'storage/'.$path)]);
            $used = Product::whereIn('main_image', $aliases)->orWhereIn('video_path', $aliases)
                ->orWhere(function ($query) use ($aliases) {
                    foreach ($aliases as $alias) {
                        $query->orWhereJsonContains('gallery_images', $alias);
                    }
                })->exists() || ProductVariant::whereIn('image', $aliases)->exists();
            if (! $used) {
                $this->delete($path);
            }
        }
    }

    private static function localPath(string $path): ?string
    {
        $path = ltrim(str_replace('\\', '/', $path), '/');
        $path = preg_replace('~^storage/~', '', $path);
        // Never delete URLs, arbitrary directories, traversal or public seed assets.
        return preg_match('~^products/(images|videos|variants)/[a-zA-Z0-9._-]+$~D', $path) ? $path : null;
    }

    private function delete(string $path): void
    {
        try {
            if (! Storage::disk('public')->delete($path)) {
                Log::warning('Product media cleanup failed.');
            }
        } catch (\Throwable $e) {
            report($e); // Failed cleanup must never roll back a committed product.
        }
    }
}
