<?php

namespace App\Http\Controllers;

use App\Models\Khata\KhataTransaction;

/** Public, token-addressed খাতা voucher (shared with the party over WhatsApp). */
class KhataVoucherController extends Controller
{
    public function show(string $token)
    {
        $tx = KhataTransaction::where('share_token', $token)->with('lines', 'party', 'vendor')->firstOrFail();

        return view('vendor.khata.voucher-public', [
            'tx'      => $tx,
            'balance' => $tx->party?->balance(),
        ]);
    }
}
