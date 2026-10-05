<?php

namespace App\Http\Controllers\Vendor\Khata;

use App\Models\Khata\KhataParty;
use App\Support\Phone;
use Illuminate\Http\Request;

class PartyController extends KhataBaseController
{
    public function index(Request $request)
    {
        $vendor  = $this->vendor();
        $parties = KhataParty::where('vendor_id', $vendor->id)->withBalance()
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', '%'.$request->q.'%')->orWhere('phone', 'like', '%'.$request->q.'%')))
            ->when(in_array($request->type, ['customer', 'supplier'], true), fn ($q) => $q->where('type', $request->type))
            ->orderBy('name')->get();

        if ($request->balance === 'receivable') {
            $parties = $parties->filter(fn ($p) => $p->balance() > 0)->values();
        } elseif ($request->balance === 'payable') {
            $parties = $parties->filter(fn ($p) => $p->balance() < 0)->values();
        }

        return view('vendor.khata.parties.index', [
            'vendor'  => $vendor,
            'parties' => $parties,
            'dues'    => $this->book->dueTotals($vendor),
        ]);
    }

    public function create(Request $request)
    {
        return view('vendor.khata.parties.form', [
            'vendor' => $this->vendor(),
            'party'  => new KhataParty(['type' => $request->input('type', 'customer'), 'opening_date' => now()]),
            'back'   => $request->input('back'),
        ]);
    }

    public function store(Request $request)
    {
        $vendor = $this->vendor();
        $party  = KhataParty::create($this->validated($request) + ['vendor_id' => $vendor->id]);

        if ($back = $this->safeBack($request->input('back'))) {
            return redirect()->to($back.(str_contains($back, '?') ? '&' : '?').'party='.$party->id)->with('success', 'পার্টি যোগ হয়েছে।');
        }

        return redirect()->route('vendor.khata.parties.show', $party)->with('success', 'পার্টি সফলভাবে যোগ হয়েছে।');
    }

    public function show(Request $request, KhataParty $party)
    {
        $this->ownParty($party);
        $txs = $party->transactions()->latest('date')->latest('id')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->paginate(30)->withQueryString();

        return view('vendor.khata.parties.show', [
            'vendor'   => $this->vendor(),
            'party'    => $party,
            'balance'  => $party->balance(),
            'txs'      => $txs,
            'reminder' => $this->reminderUrl($party),
        ]);
    }

    /** Running-balance statement (printable). */
    public function statement(KhataParty $party)
    {
        $this->ownParty($party);
        $running = (float) $party->opening_balance;
        $rows = $party->transactions()->orderBy('date')->orderBy('id')->get()->map(function ($tx) use (&$running) {
            $move = match ($tx->type) {
                'sale'        => (float) $tx->total - (float) $tx->paid,
                'payment_in'  => -(float) $tx->paid,
                'purchase'    => -((float) $tx->total - (float) $tx->paid),
                'payment_out' => (float) $tx->paid,
                default       => 0.0,
            };
            $running = round($running + $move, 2);

            return ['tx' => $tx, 'move' => $move, 'balance' => $running];
        });

        return view('vendor.khata.parties.statement', ['vendor' => $this->vendor(), 'party' => $party, 'rows' => $rows]);
    }

    public function edit(KhataParty $party)
    {
        $this->ownParty($party);

        return view('vendor.khata.parties.form', ['vendor' => $this->vendor(), 'party' => $party, 'back' => null]);
    }

    public function update(Request $request, KhataParty $party)
    {
        $this->ownParty($party);
        $party->update($this->validated($request));

        return redirect()->route('vendor.khata.parties.show', $party)->with('success', 'পার্টির তথ্য আপডেট হয়েছে।');
    }

    public function destroy(KhataParty $party)
    {
        $this->ownParty($party);
        if ($party->transactions()->exists()) {
            return back()->with('error', 'এই পার্টির লেনদেন আছে — আগে লেনদেনগুলো মুছুন।');
        }
        $party->delete();

        return redirect()->route('vendor.khata.parties.index')->with('success', 'পার্টি মুছে ফেলা হয়েছে।');
    }

    private function validated(Request $request): array
    {
        $request->merge(['phone' => $request->filled('phone') ? (Phone::normalize($request->phone) ?? $request->phone) : null]);
        $data = $request->validate([
            'name'            => ['required', 'string', 'max:150'],
            'phone'           => ['nullable', 'string', 'max:20'],
            'address'         => ['nullable', 'string', 'max:300'],
            'type'            => ['required', 'in:customer,supplier'],
            'opening_amount'  => ['nullable', 'numeric', 'min:0'],
            'opening_side'    => ['nullable', 'in:receivable,payable'],
            'opening_date'    => ['nullable', 'date'],
            'note'            => ['nullable', 'string', 'max:500'],
        ], ['name.required' => 'পার্টির নাম দিন।']);

        $amount = (float) ($data['opening_amount'] ?? 0);
        $data['opening_balance'] = ($data['opening_side'] ?? 'receivable') === 'payable' ? -$amount : $amount;
        unset($data['opening_amount'], $data['opening_side']);

        return $data;
    }

    private function reminderUrl(KhataParty $party): ?string
    {
        $wa = Phone::toWa($party->phone);
        if (! $wa) {
            return null;
        }
        $b    = $party->balance();
        $shop = $this->vendor()->shop_name;
        $text = $b > 0
            ? "আসসালামু আলাইকুম {$party->name},\n{$shop}-এ আপনার বাকি ৳".number_format($b, 0)." রয়েছে। সুবিধামতো সময়ে পরিশোধ করার অনুরোধ রইল। ধন্যবাদ।"
            : "আসসালামু আলাইকুম {$party->name},\n{$shop} থেকে শুভেচ্ছা।";

        return 'https://wa.me/'.$wa.'?text='.rawurlencode($text);
    }

    private function safeBack(?string $back): ?string
    {
        return $back && str_starts_with($back, '/') && ! str_starts_with($back, '//') ? $back : null;
    }
}
