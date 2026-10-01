<?php

namespace App\Http\Controllers;

use App\Support\TempUpload;
use App\Support\UploadErrors;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POST admin/products/uploads and vendor/products/uploads: one image per request
 * (see TempUpload). Returns a token + preview URL; nothing is attached to a product
 * until the editor form is saved.
 */
class ProductImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'vendor' && ! $user->vendor?->isApproved()) {
            abort(403, 'অ্যাকাউন্ট অনুমোদিত হয়নি।');
        }

        $label = match ($request->input('kind')) {
            'og' => 'শেয়ার ছবি', 'gallery' => 'গ্যালারির ছবি', 'variant' => 'ভ্যারিয়েন্টের ছবি', 'wholesale_main' => 'পাইকারি কভার ছবি', default => 'মূল ছবি',
        };
        UploadErrors::guard($request, ['file' => $label]);
        $request->validate([
            'kind' => 'required|in:'.implode(',', array_keys(TempUpload::KINDS)),
            'file' => ['bail', 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'extensions:jpg,jpeg,png,webp', 'max:10240'],
        ], UploadErrors::imageMessages(['file']) + ['kind.in' => 'ছবির ধরন সঠিক নয়।'], ['file' => $label]);

        // Stale temp files are cleaned opportunistically (no cron needed).
        if (random_int(1, 20) === 1) {
            TempUpload::cleanup();
        }

        try {
            $result = TempUpload::store($request->file('file'), $request->input('kind'), (int) $user->id);
        } catch (\Throwable $e) {
            Log::warning('Temp product image upload failed', ['error' => get_class($e), 'user_id' => $user->id]);
            return response()->json(['message' => "{$label} সংরক্ষণ করা যায়নি। আবার চেষ্টা করুন।"], 500);
        }

        return response()->json(['token' => $result['token'], 'url' => $result['url']]);
    }
}
