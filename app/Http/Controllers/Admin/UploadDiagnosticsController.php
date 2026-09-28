<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use App\Support\ServerLimits;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * "আপলোড সীমা পরীক্ষা": the browser POSTs growing payloads here. Nothing is stored —
 * we only report what arrived. A 413 from nginx never reaches PHP (the browser sees
 * an HTML 413); a too-big body for PHP's post_max_size is a JSON 413 from Laravel.
 */
class UploadDiagnosticsController extends Controller
{
    public function probe(Request $request)
    {
        $file = $request->file('probe');
        if (! $file) {
            return response()->json(['ok' => false, 'layer' => 'php_empty', 'bytes' => 0]);
        }
        if (! $file->isValid()) {
            return response()->json([
                'ok' => false, 'bytes' => 0,
                'layer' => $file->getError() === UPLOAD_ERR_INI_SIZE ? 'php_upload_max_filesize' : 'php_upload_error',
                'error' => ServerLimits::uploadErrorName($file->getError()),
            ]);
        }

        return response()->json(['ok' => true, 'bytes' => (int) $file->getSize()]);
    }

    /** Remember the largest size that got through, for the client-side guard. */
    public function saveResult(Request $request)
    {
        $data = $request->validate(['largest_ok_bytes' => 'required|integer|min:0|max:1073741824']);

        WebsiteSetting::updateOrCreate(['key' => ServerLimits::MEASURED_KEY], ['value' => (string) $data['largest_ok_bytes']]);
        WebsiteSetting::updateOrCreate(['key' => ServerLimits::MEASURED_AT_KEY], ['value' => now()->toIso8601String()]);
        Log::info('Upload limit test', [
            'user_id' => $request->user()?->id, 'largest_ok_bytes' => (int) $data['largest_ok_bytes'],
            'upload_max_filesize' => ini_get('upload_max_filesize'), 'post_max_size' => ini_get('post_max_size'),
        ]);

        return response()->json(['ok' => true, 'saved' => ServerLimits::human((int) $data['largest_ok_bytes'])]);
    }
}
