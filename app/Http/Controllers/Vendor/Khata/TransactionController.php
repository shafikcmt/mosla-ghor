<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Models\Khata\KhataItem;
use App\Models\Khata\KhataParty;
use App\Models\Khata\KhataTransaction;
use App\Support\KhataStaff;
use App\Support\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionController extends KhataBaseController
{
    /** বিক্রি / ক্রয় form. */
    public function createTrade(Request $request, string $type)
    {
        abort_unless(in_array($type, ['sale', 'purchase'], true), 404);
        $vendor = $this->vendor();

        return view('vendor.khata.trade-form', [
            'vendor'  => $vendor,
            'type'    => $type,
            'number'  => $this->book->nextNumber($vendor, $type),
            'items'   => KhataItem::where('vendor_id', $vendor->id)->where('is_active', true)->orderBy('name')->get(),
            'parties' => KhataParty::where('vendor_id', $vendor->id)->withBalance()->orderBy('name')->get(),
            'partyId' => $request->integer('party') ?: null,
        ]);
    }

    public function storeTrade(Request $request, string $type)
    {
        abort_unless(in_array($type, ['sale', 'purchase'], true), 404);
        $vendor = $this->vendor();
        $data = $this->validTrade($request, $vendor->id, $type);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $tx = $this->book->recordWithItems($vendor, $type, $data, $data['lines']);

        return redirect()->route('vendor.khata.tx.show', $tx)
            ->with('success', ($type === 'sale' ? 'বিক্রি' : 'ক্রয়').' #'.$tx->number.' সংরক্ষণ হয়েছে।');
    }

    /** Edit a saved বিক্রি / ক্রয় — same form, prefilled. */
    public function editTrade(KhataTransaction $tx)
    {
        $this->editableTrade($tx);
        $vendor = $this->vendor();
        $tx->load('lines');
        $lineItemIds = $tx->lines->pluck('item_id')->filter()->all();

        return view('vendor.khata.trade-form', [
            'vendor'  => $vendor,
            'type'    => $tx->type,
            'tx'      => $tx,
            'number'  => $tx->number,
            // Inactive items already on this entry stay selectable.
            'items'   => KhataItem::where('vendor_id', $vendor->id)
                ->where(fn ($q) => $q->where('is_active', true)->orWhereIn('id', $lineItemIds))->orderBy('name')->get(),
            'parties' => KhataParty::where('vendor_id', $vendor->id)->withBalance()->orderBy('name')->get(),
            'partyId' => $tx->party_id,
        ]);
    }

    public function updateTrade(Request $request, KhataTransaction $tx)
    {
        $this->editableTrade($tx);
        $data = $this->validTrade($request, $tx->vendor_id, $tx->type);
        if ($data instanceof \Illuminate\Http\RedirectResponse) {
            return $data;
        }

        $tx = $this->book->updateWithItems($tx, $data, $data['lines']);

        return redirect()->route('vendor.khata.tx.show', $tx)
            ->with('success', $tx->typeLabel().' #'.$tx->number.' আপডেট হয়েছে — স্টক নতুন করে হিসাব হয়েছে।');
    }

    /** Only sales / purchases with all lines still linked to items; staff need the type permission and own the entry (or ledger access). */
    private function editableTrade(KhataTransaction $tx): void
    {
        $this->ownTx($tx);
        abort_unless(in_array($tx->type, ['sale', 'purchase'], true), 404);
        abort_unless(KhataStaff::canEdit(Auth::user(), $tx), 403, 'এই এন্ট্রি সম্পাদনার অনুমতি আপনার নেই।');
        abort_if($tx->lines()->whereNull('item_id')->exists(), 409, 'এই এন্ট্রির একটি আইটেম মুছে ফেলা হয়েছে — সম্পাদনা করা যাবে না। দরকার হলে মুছে নতুন করে লিখুন।');
    }

    /** Shared rules for creating and editing a sale / purchase. */
    private function validTrade(Request $request, int $vendorId, string $type): array|\Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'party_id'        => ['nullable', 'integer', Rule::exists('khata_parties', 'id')->where('vendor_id', $vendorId)],
            'date'            => ['required', 'date'],
            'lines'           => ['required', 'array', 'min:1', 'max:60'],
            'lines.*.item_id' => ['required', 'integer', Rule::exists('khata_items', 'id')->where('vendor_id', $vendorId)],
            'lines.*.quantity'=> ['required', 'numeric', 'min:0.001'],
            'lines.*.price'   => ['required', 'numeric', 'min:0'],
            'discount'        => ['nullable', 'numeric', 'min:0'],
            'extra_label'     => ['nullable', 'string', 'max:60'],
            'extra_charge'    => ['nullable', 'numeric', 'min:0'],
            'paid'            => ['nullable', 'numeric', 'min:0'],
            'payment_mode'    => ['required', 'in:'.implode(',', array_keys(KhataTransaction::MODES))],
            'note'            => ['nullable', 'string', 'max:500'],
        ], [
            'lines.required'  => 'অন্তত একটি আইটেম যোগ করুন।',
            'lines.*.item_id.required' => 'প্রতিটি সারিতে আইটেম বেছে নিন।',
            'lines.*.quantity.required' => 'প্রতিটি সারিতে পরিমাণ দিন (যেমন: ২৭ কেজি ৭০০ গ্রাম)।',
            'lines.*.quantity.min' => 'পরিমাণ ০-এর বেশি হতে হবে।',
        ]);

        // A due (বাকি) needs someone to owe it.
        $subtotal = collect($data['lines'])->sum(fn ($l) => (float) $l['quantity'] * (float) $l['price']);
        $total    = $subtotal - min((float) ($data['discount'] ?? 0), $subtotal) + (float) ($data['extra_charge'] ?? 0);
        if (empty($data['party_id']) && (float) ($data['paid'] ?? 0) + 0.009 < round($total, 2)) {
            return back()->withInput()->with('error', 'বাকিতে '.($type === 'sale' ? 'বিক্রি' : 'ক্রয়').' করতে পার্টি বেছে নিন — অথবা পুরো টাকা পরিশোধ দেখান।');
        }

        return $data;
    }

    /** পেমেন্ট ইন / আউট and খরচ form. */
    public function createAmount(Request $request, string $type)
    {
        abort_unless(in_array($type, ['payment_in', 'payment_out', 'expense'], true), 404);
        $vendor = $this->vendor();

        return view('vendor.khata.amount-form', [
            'vendor'     => $vendor,
            'type'       => $type,
            'parties'    => KhataParty::where('vendor_id', $vendor->id)->withBalance()->orderBy('name')->get(),
            'partyId'    => $request->integer('party') ?: null,
            'categories' => KhataTransaction::where('vendor_id', $vendor->id)->where('type', 'expense')->whereNotNull('category')->distinct()->pluck('category')
                ->merge(['দোকান ভাড়া', 'বিদ্যুৎ বিল', 'কর্মচারীর বেতন', 'পরিবহন', 'নাস্তা', 'অন্যান্য'])->unique()->values(),
        ]);
    }

    public function storeAmount(Request $request, string $type)
    {
        abort_unless(in_array($type, ['payment_in', 'payment_out', 'expense'], true), 404);
        $vendor = $this->vendor();
        $data = $request->validate([
            'party_id'     => [$type === 'expense' ? 'nullable' : 'required', 'nullable', 'integer', Rule::exists('khata_parties', 'id')->where('vendor_id', $vendor->id)],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'date'         => ['required', 'date'],
            'payment_mode' => ['required', 'in:cash,bkash,nagad,bank'],
            'category'     => ['nullable', 'string', 'max:80'],
            'note'         => ['nullable', 'string', 'max:500'],
        ], ['party_id.required' => 'পার্টি বেছে নিন।', 'amount.required' => 'টাকার পরিমাণ দিন।']);

        $tx = $this->book->recordAmount($vendor, $type, $data);

        if (! KhataStaff::can(Auth::user(), 'ledger')) {
            return redirect()->route('vendor.khata.home')->with('success', $tx->typeLabel().' ৳'.number_format((float) $tx->total, 0).' সংরক্ষণ হয়েছে।');
        }

        if ($tx->party_id) {
            return redirect()->route('vendor.khata.parties.show', $tx->party_id)->with('success', $tx->typeLabel().' ৳'.number_format((float) $tx->total, 0).' সংরক্ষণ হয়েছে।');
        }

        return redirect()->route('vendor.khata.transactions')->with('success', $tx->typeLabel().' সংরক্ষণ হয়েছে।');
    }

    /** Voucher (বিক্রয় / ক্রয় বিবরণ) or receipt. */
    public function show(KhataTransaction $tx)
    {
        $this->ownTx($tx);
        // Staff without ledger access may open only the entries they wrote themselves.
        $ledger = KhataStaff::can(Auth::user(), 'ledger');
        abort_unless($ledger || $tx->created_by === Auth::id(), 403, 'এই এন্ট্রি দেখার অনুমতি আপনার নেই।');
        $tx->load('lines', 'party', 'vendor');
        $tx->ensureShareToken();

        return view('vendor.khata.voucher-page', [
            'vendor'   => $this->vendor(),
            'tx'       => $tx,
            'balance'  => $ledger ? $tx->party?->balance() : null,
            'whatsapp' => $this->whatsappUrl($tx),
        ]);
    }

    public function destroy(KhataTransaction $tx)
    {
        $this->ownTx($tx);
        $label = $tx->typeLabel().($tx->number ? ' #'.$tx->number : '');
        $party = $tx->party_id;
        $this->book->delete($tx);

        return ($party
            ? redirect()->route('vendor.khata.parties.show', $party)
            : redirect()->route('vendor.khata.transactions'))
            ->with('success', "{$label} মুছে ফেলা হয়েছে (স্টক ঠিক করা হয়েছে)।");
    }

    private function whatsappUrl(KhataTransaction $tx): ?string
    {
        $wa = Phone::toWa($tx->party?->phone);
        if (! $wa) {
            return null;
        }
        $shop  = $tx->vendor->shop_name;
        $lines = $tx->lines->map(fn ($l) => '• '.$l->name.' — '.KhataItem::formatQty($l->quantity, $l->unit).' × ৳'.number_format((float) $l->price, 0).' = ৳'.number_format((float) $l->total, 0))->implode("\n");
        $bal   = $tx->party->balance();

        $text = "*{$shop}* — {$tx->typeLabel()}".($tx->number ? " #{$tx->number}" : '')."\n"
            ."তারিখ: ".$tx->date->format('d M Y')."\n\n"
            .($lines ? $lines."\n\n" : '')
            ."মোট: ৳".number_format((float) $tx->total, 0)."\n"
            .((float) $tx->paid > 0 && in_array($tx->type, ['sale', 'purchase'], true) ? 'পরিশোধ: ৳'.number_format((float) $tx->paid, 0)."\n" : '')
            .($bal != 0 ? ($bal > 0 ? 'আপনার মোট বাকি: ৳' : 'আমাদের দেনা: ৳').number_format(abs($bal), 0)."\n" : '')
            ."\nবিস্তারিত: ".$tx->publicUrl()."\nধন্যবাদ।";

        return 'https://wa.me/'.$wa.'?text='.rawurlencode($text);
    }
}
