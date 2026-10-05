<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Http\Controllers\Controller;
use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataParty;
use App\Models\Khata\KhataTransaction;
use App\Models\Vendor;
use App\Services\KhataBook;
use Illuminate\Support\Facades\Auth;

/** Shared guard + ownership checks for দোকানের খাতা. */
abstract class KhataBaseController extends Controller
{
    public function __construct(protected KhataBook $book)
    {
    }

    protected function vendor(): Vendor
    {
        $vendor = Auth::user()->vendor;
        abort_unless($vendor, 403);
        if (! $vendor->isApproved()) {
            abort(403, 'আপনার অ্যাকাউন্ট এখনো অনুমোদিত হয়নি — অনুমোদনের পর খাতা ব্যবহার করতে পারবেন।');
        }

        return $vendor;
    }

    protected function ownItem(KhataItem $item): KhataItem
    {
        abort_unless($item->vendor_id === $this->vendor()->id, 404);

        return $item;
    }

    protected function ownParty(KhataParty $party): KhataParty
    {
        abort_unless($party->vendor_id === $this->vendor()->id, 404);

        return $party;
    }

    protected function ownTx(KhataTransaction $tx): KhataTransaction
    {
        abort_unless($tx->vendor_id === $this->vendor()->id, 404);

        return $tx;
    }
}
