@extends('vendor.khata.layout')
@php
    $isSale = $type === 'sale';
    $tx     = $tx ?? null; // set when editing a saved entry
    $title  = $tx ? ($isSale ? 'বিক্রি সম্পাদনা' : 'ক্রয় সম্পাদনা') : ($isSale ? 'বিক্রি যোগ করুন' : 'ক্রয় যোগ করুন');
    // When editing, show stock as it was before this entry (its own quantities added back).
    $sign   = $isSale ? 1 : -1;
    $own    = $tx ? $tx->lines->groupBy('item_id')->map(fn ($l) => (float) $l->sum('quantity')) : collect();
    $inp    = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $itemData = $items->mapWithKeys(fn ($i) => [$i->id => [
        'name' => $i->name, 'unit' => $i->unitLabel(), 'kg' => $i->unit === 'kg', 'stock' => (float) $i->stock + $sign * ($own[$i->id] ?? 0),
        'price' => (float) ($isSale ? $i->sale_price : $i->purchase_price),
    ]]);
    $partyData = $parties->mapWithKeys(fn ($p) => [$p->id => ['balance' => $p->balance(), 'phone' => $p->phone]]);
    $defLines = $tx
        ? $tx->lines->map(fn ($l) => ['item_id' => $l->item_id, 'quantity' => (float) $l->quantity, 'price' => (float) $l->price])->values()->all()
        : [['item_id' => '', 'quantity' => 1, 'price' => '']];
    $oldLines = old('lines', $defLines);
    $val = fn ($key, $default = null) => old($key, $tx ? $tx->{$key} : $default);
    $num = fn ($key) => ($v = $val($key)) !== null && (float) $v > 0 ? (float) $v : null;
    $selParty = old('party_id', $partyId);
@endphp
@section('title', $title)
@section('heading', $title)
@section('back', $tx ? route('vendor.khata.tx.show', $tx) : ($selParty ? route('vendor.khata.parties.show', $selParty) : route('vendor.khata.home')))

@section('content')
<form method="POST" action="{{ $tx ? route('vendor.khata.tx.update', $tx) : route('vendor.khata.trade.store', $type) }}" id="kh-trade" class="space-y-3 max-w-3xl lg:max-w-none lg:grid lg:grid-cols-5 lg:gap-6 lg:space-y-0 lg:items-start">
    @csrf
    @if($tx) @method('PUT') @endif
    <div class="space-y-3 lg:col-span-3">
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 grid grid-cols-2 gap-3">
        <div class="col-span-2 flex items-center justify-between gap-3">
            <p class="text-sm text-gray-500">{{ $isSale ? 'চালান' : 'ক্রয়' }} <b class="text-gray-900 num">#{{ $number }}</b></p>
            <input type="date" name="date" value="{{ old('date', $tx ? $tx->date->toDateString() : now()->toDateString()) }}" required aria-label="তারিখ" class="border border-gray-200 rounded-lg px-2 py-1.5 text-sm">
        </div>
        <div class="col-span-2">
            <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold">{{ $isSale ? 'গ্রাহক / পার্টি' : 'সরবরাহকারী / পার্টি' }}</label>
                <a href="{{ route('vendor.khata.parties.create', ['type' => $isSale ? 'customer' : 'supplier', 'back' => request()->getRequestUri()]) }}" class="text-xs font-semibold text-[#0f7a3e]">+ নতুন পার্টি</a>
            </div>
            <select name="party_id" id="kh-party" class="{{ $inp }}">
                <option value="">{{ $isSale ? 'নগদ বিক্রি (পার্টি ছাড়া)' : 'নগদ ক্রয় (পার্টি ছাড়া)' }}</option>
                @foreach($parties as $p)
                <option value="{{ $p->id }}" @selected((string) $selParty === (string) $p->id)>{{ $p->name }}{{ $p->phone ? ' — '.$p->phone : '' }}</option>
                @endforeach
            </select>
            <p id="kh-party-bal" class="text-xs mt-1 hidden"></p>
        </div>
    </div>

    {{-- Lines --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <p class="font-bold mb-2">{{ $isSale ? 'বিলিং আইটেম' : 'ক্রয়ের আইটেম' }}</p>
        @if($items->isEmpty())
            <p class="text-sm text-amber-700 bg-amber-50 rounded-xl p-3">আগে <a class="underline font-semibold" href="{{ route('vendor.khata.items.create') }}">আইটেম যোগ করুন</a>, তারপর {{ $isSale ? 'বিক্রি' : 'ক্রয়' }} লিখুন।</p>
        @endif
        <div id="kh-lines" class="space-y-2">
            @foreach($oldLines as $i => $l)
            <div class="kh-line rounded-xl border border-gray-100 bg-gray-50 p-2.5">
                <div class="flex gap-2">
                    <select name="lines[{{ $i }}][item_id]" required class="kh-item flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-2 bg-white">
                        <option value="">— আইটেম বেছে নিন —</option>
                        @foreach($items as $it)<option value="{{ $it->id }}" @selected((string) ($l['item_id'] ?? '') === (string) $it->id)>{{ $it->name }}</option>@endforeach
                    </select>
                    <button type="button" class="kh-del text-red-500 px-2 text-xl" aria-label="সারি মুছুন">🗑</button>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 items-end">
                    <div class="col-span-2 text-xs text-gray-500">
                        পরিমাণ <span class="kh-unit"></span>
                        <input type="number" name="lines[{{ $i }}][quantity]" value="{{ $l['quantity'] ?? 1 }}" step="0.001" min="0.001" required inputmode="decimal" class="kh-qty w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
                        {{-- কেজি items: whole kg + grams (e.g. 27 কেজি 700 গ্রাম) fill the quantity above. --}}
                        <div class="kh-kgg grid grid-cols-2 gap-2" style="display:none">
                            <div class="relative"><input type="number" min="0" step="1" inputmode="numeric" placeholder="0" aria-label="কেজি" class="kh-kg w-full border border-gray-200 rounded-lg pl-2 pr-11 py-2 bg-white text-base text-gray-800"><span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">কেজি</span></div>
                            <div class="relative"><input type="number" min="0" max="999" step="1" inputmode="numeric" placeholder="0" aria-label="গ্রাম" class="kh-g w-full border border-gray-200 rounded-lg pl-2 pr-11 py-2 bg-white text-base text-gray-800"><span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">গ্রাম</span></div>
                        </div>
                    </div>
                    <label class="text-xs text-gray-500">মূল্য (৳<span class="kh-per"></span>)
                        <input type="number" name="lines[{{ $i }}][price]" value="{{ $l['price'] ?? '' }}" step="0.01" min="0" required inputmode="decimal" class="kh-price w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
                    </label>
                    <div class="text-right">
                        <p class="text-xs text-gray-500">মোট</p>
                        <p class="kh-total font-bold num">৳0</p>
                    </div>
                </div>
                <p class="kh-stock text-[11px] text-gray-400 mt-1"></p>
            </div>
            @endforeach
        </div>
        <button type="button" id="kh-add" class="mt-2 w-full border-2 border-dashed border-green-300 text-[#0f7a3e] font-semibold py-2.5 rounded-xl">+ পণ্য যোগ করুন</button>
    </div>

    </div>

    <div class="space-y-3 lg:col-span-2 lg:sticky lg:top-20">
    {{-- Totals: optional extras open on demand --}}
    @php $hasDisc = (float) $val('discount') > 0; $hasExtra = (float) $val('extra_charge') > 0; $hasNote = filled($val('note')); @endphp
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-3">
        <div class="flex justify-between"><span class="text-gray-500">উপমোট</span><span id="kh-sub" class="font-semibold num">৳0</span></div>

        <div id="kh-disc-row" class="flex items-center justify-between gap-2 {{ $hasDisc ? '' : 'hidden' }}">
            <span class="text-gray-500">ডিসকাউন্ট</span>
            <input type="number" name="discount" id="kh-disc" value="{{ $num('discount') }}" step="0.01" min="0" placeholder="৳ 0" class="w-32 border border-gray-200 rounded-lg px-2 py-2 text-right">
        </div>
        <div id="kh-extra-row" class="flex items-center justify-between gap-2 {{ $hasExtra ? '' : 'hidden' }}">
            <input name="extra_label" value="{{ $val('extra_label') }}" maxlength="60" placeholder="যেমন: লেবার ভাড়া" class="flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-2">
            <input type="number" name="extra_charge" id="kh-extra" value="{{ $num('extra_charge') }}" step="0.01" min="0" placeholder="৳ 0" class="w-32 border border-gray-200 rounded-lg px-2 py-2 text-right">
        </div>
        <div class="flex flex-wrap gap-2 text-sm">
            <button type="button" data-show="kh-disc-row" class="kh-chip {{ $hasDisc ? 'hidden' : '' }} text-[#0f7a3e] font-semibold">+ ডিসকাউন্ট</button>
            <button type="button" data-show="kh-extra-row" class="kh-chip {{ $hasExtra ? 'hidden' : '' }} text-[#0f7a3e] font-semibold">+ অতিরিক্ত চার্জ</button>
            <button type="button" data-show="kh-note-row" class="kh-chip {{ $hasNote ? 'hidden' : '' }} text-[#0f7a3e] font-semibold">+ নোট</button>
        </div>

        <div class="flex justify-between border-t pt-3 text-lg"><span class="font-bold">মোট</span><span id="kh-grand" class="font-bold num">৳0</span></div>

        <div class="flex items-center justify-between gap-2">
            <span class="font-semibold">{{ $isSale ? 'টাকা পেয়েছি' : 'টাকা দিয়েছি' }}</span>
            <div class="flex items-center gap-2">
                <button type="button" id="kh-full" class="text-xs font-semibold text-[#0f7a3e] border border-green-200 rounded-lg px-2.5 py-2">পুরো টাকা</button>
                <input type="number" name="paid" id="kh-paid" value="{{ $num('paid') }}" step="0.01" min="0" placeholder="৳ 0" inputmode="decimal" class="w-32 border border-gray-200 rounded-lg px-2 py-2 text-right">
            </div>
        </div>
        <div id="kh-mode-row" class="flex items-center justify-between gap-2 hidden">
            <span class="text-gray-500">কীভাবে</span>
            <div class="flex gap-1 text-xs">
                @foreach(['cash' => 'নগদ', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'ব্যাংক'] as $k => $l)
                <label class="cursor-pointer">
                    <input type="radio" name="payment_mode" value="{{ $k }}" class="peer sr-only" @checked(old('payment_mode', $tx && $tx->payment_mode !== 'credit' ? $tx->payment_mode : 'cash') === $k)>
                    <span class="block px-2.5 py-1.5 rounded-lg border border-gray-200 font-semibold peer-checked:bg-gray-800 peer-checked:text-white peer-checked:border-gray-800">{{ $l }}</span>
                </label>
                @endforeach
            </div>
        </div>
        <div class="flex justify-between"><span class="font-semibold">বাকি</span><span id="kh-due" class="font-bold text-red-600 num">৳0</span></div>
        <div id="kh-note-row" class="{{ $hasNote ? '' : 'hidden' }}">
            <input name="note" value="{{ $val('note') }}" maxlength="500" placeholder="নোট" class="w-full border border-gray-200 rounded-lg px-3 py-2">
        </div>
    </div>

    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl text-lg shadow">{{ $tx ? 'আপডেট করুন' : 'সেভ করুন' }}</button>
    @if($tx)<p class="text-xs text-gray-500 text-center">সেভ করলে আগের হিসাবের স্টক ফেরত যাবে এবং নতুন পরিমাণে স্টক আবার হিসাব হবে।</p>@endif
    </div>
</form>

<template id="kh-line-tpl">
    <div class="kh-line rounded-xl border border-gray-100 bg-gray-50 p-2.5">
        <div class="flex gap-2">
            <select name="lines[__I__][item_id]" required class="kh-item flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-2 bg-white">
                <option value="">— আইটেম বেছে নিন —</option>
                @foreach($items as $it)<option value="{{ $it->id }}">{{ $it->name }}</option>@endforeach
            </select>
            <button type="button" class="kh-del text-red-500 px-2 text-xl" aria-label="সারি মুছুন">🗑</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mt-2 items-end">
            <div class="col-span-2 text-xs text-gray-500">
                পরিমাণ <span class="kh-unit"></span>
                <input type="number" name="lines[__I__][quantity]" value="1" step="0.001" min="0.001" required inputmode="decimal" class="kh-qty w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
                {{-- কেজি items: whole kg + grams (e.g. 27 কেজি 700 গ্রাম) fill the quantity above. --}}
                <div class="kh-kgg grid grid-cols-2 gap-2" style="display:none">
                    <div class="relative"><input type="number" min="0" step="1" inputmode="numeric" placeholder="0" aria-label="কেজি" class="kh-kg w-full border border-gray-200 rounded-lg pl-2 pr-11 py-2 bg-white text-base text-gray-800"><span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">কেজি</span></div>
                    <div class="relative"><input type="number" min="0" max="999" step="1" inputmode="numeric" placeholder="0" aria-label="গ্রাম" class="kh-g w-full border border-gray-200 rounded-lg pl-2 pr-11 py-2 bg-white text-base text-gray-800"><span class="absolute right-2 top-1/2 -translate-y-1/2 text-xs text-gray-400">গ্রাম</span></div>
                </div>
            </div>
            <label class="text-xs text-gray-500">মূল্য (৳<span class="kh-per"></span>)
                <input type="number" name="lines[__I__][price]" step="0.01" min="0" required inputmode="decimal" class="kh-price w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
            </label>
            <div class="text-right">
                <p class="text-xs text-gray-500">মোট</p>
                <p class="kh-total font-bold num">৳0</p>
            </div>
        </div>
        <p class="kh-stock text-[11px] text-gray-400 mt-1"></p>
    </div>
</template>
@endsection

@push('scripts')
<script>
(function () {
    const ITEMS = @json($itemData), PARTIES = @json($partyData), IS_SALE = @json($isSale);
    const box = document.getElementById('kh-lines');
    let idx = box.querySelectorAll('.kh-line').length;
    const num = el => parseFloat(el && el.value) || 0;
    const q3 = n => (Math.round(n * 1000) / 1000).toString();

    // কেজি items: show কেজি + গ্রাম boxes; the hidden quantity input carries kg (27.7).
    const fmtQty = (n, it) => {
        if (!it || !it.kg) return q3(n) + (it ? ' ' + it.unit : '');
        const neg = n < 0, a = Math.abs(Math.round(n * 1000) / 1000), kg = Math.floor(a + 1e-9), g = Math.round((a - kg) * 1000);
        return (neg ? '−' : '') + (g ? (kg ? kg + ' কেজি ' : '') + g + ' গ্রাম' : kg + ' কেজি');
    };
    function kgMode(line, it) {
        const qty = line.querySelector('.kh-qty'), box = line.querySelector('.kh-kgg');
        const on = !!(it && it.kg);
        if (on && box.style.display === 'none') {
            const v = Math.round(num(qty) * 1000), kg = Math.floor(v / 1000);
            line.querySelector('.kh-kg').value = kg || (v ? 0 : '');
            line.querySelector('.kh-g').value = v % 1000 || '';
        }
        box.style.display = on ? '' : 'none';
        qty.style.display = on ? 'none' : '';
        qty.required = !on; // the কেজি/গ্রাম boxes are the visible fields
    }
    function fromKgG(line) {
        const g = Math.min(999, Math.max(0, Math.round(num(line.querySelector('.kh-g')))));
        const kg = Math.max(0, Math.floor(num(line.querySelector('.kh-kg'))));
        line.querySelector('.kh-qty').value = (kg + g / 1000) ? q3(kg + g / 1000) : '';
    }

    function fill(line, setPrice) {
        const it = ITEMS[line.querySelector('.kh-item').value];
        line.querySelector('.kh-unit').textContent = it && !it.kg ? '(' + it.unit + ')' : '';
        line.querySelector('.kh-per').textContent = it && it.kg ? ' / কেজি' : '';
        kgMode(line, it);
        const price = line.querySelector('.kh-price');
        if (it && (setPrice || !price.value)) price.value = it.price || '';
        const st = line.querySelector('.kh-stock');
        if (!it) { st.textContent = ''; return; }
        const left = it.stock - (IS_SALE ? num(line.querySelector('.kh-qty')) : -num(line.querySelector('.kh-qty')));
        st.textContent = 'স্টক: ' + fmtQty(it.stock, it) + (IS_SALE ? ' → বিক্রির পর ' + fmtQty(left, it) : '');
        st.className = 'kh-stock text-[11px] mt-1 ' + (IS_SALE && left < 0 ? 'text-red-600 font-semibold' : 'text-gray-400');
    }
    function recalc() {
        let sub = 0;
        box.querySelectorAll('.kh-line').forEach(l => {
            const t = num(l.querySelector('.kh-qty')) * num(l.querySelector('.kh-price'));
            l.querySelector('.kh-total').textContent = khTaka(t);
            sub += t;
            if (l.querySelector('.kh-item').value) fill(l, false);
        });
        const grand = Math.max(0, sub - Math.min(num(document.getElementById('kh-disc')), sub) + num(document.getElementById('kh-extra')));
        const paid = Math.min(num(document.getElementById('kh-paid')), grand);
        document.getElementById('kh-sub').textContent = khTaka(sub);
        document.getElementById('kh-grand').textContent = khTaka(grand);
        document.getElementById('kh-due').textContent = khTaka(grand - paid);
        return grand;
    }
    function partyInfo() {
        const p = PARTIES[document.getElementById('kh-party').value], el = document.getElementById('kh-party-bal');
        if (!p) { el.classList.add('hidden'); return; }
        el.classList.remove('hidden');
        el.className = 'text-xs mt-1 ' + (p.balance > 0 ? 'text-green-700' : p.balance < 0 ? 'text-red-600' : 'text-gray-500');
        el.textContent = p.balance > 0 ? 'বর্তমান পাওনা: ' + khTaka(p.balance) : p.balance < 0 ? 'বর্তমান বকেয়া: ' + khTaka(-p.balance) : 'কোনো বাকি নেই';
    }

    document.getElementById('kh-add').addEventListener('click', () => {
        box.insertAdjacentHTML('beforeend', document.getElementById('kh-line-tpl').innerHTML.replaceAll('__I__', idx++));
        box.lastElementChild.querySelector('.kh-item').focus();
        recalc();
    });
    box.addEventListener('click', e => {
        if (e.target.closest('.kh-del') && box.querySelectorAll('.kh-line').length > 1) { e.target.closest('.kh-line').remove(); recalc(); }
    });
    box.addEventListener('change', e => { if (e.target.classList.contains('kh-item')) { fill(e.target.closest('.kh-line'), true); recalc(); } });
    // Runs before the form's recalc listener (the event bubbles from here up to the form).
    box.addEventListener('input', e => { if (e.target.matches('.kh-kg, .kh-g')) fromKgG(e.target.closest('.kh-line')); });
    document.getElementById('kh-trade').addEventListener('input', recalc);
    document.getElementById('kh-full').addEventListener('click', () => { document.getElementById('kh-paid').value = Math.round(recalc() * 100) / 100; recalc(); });
    document.getElementById('kh-party').addEventListener('change', partyInfo);
    document.querySelectorAll('.kh-chip').forEach(c => c.addEventListener('click', () => {
        const row = document.getElementById(c.dataset.show);
        row.classList.remove('hidden'); c.classList.add('hidden');
        const f = row.querySelector('input:not([name=extra_label])') || row.querySelector('input'); if (f) f.focus();
    }));
    const modeRow = document.getElementById('kh-mode-row');
    const syncMode = () => modeRow.classList.toggle('hidden', !(num(document.getElementById('kh-paid')) > 0));
    document.getElementById('kh-paid').addEventListener('input', syncMode);
    document.getElementById('kh-full').addEventListener('click', syncMode);

    box.querySelectorAll('.kh-line').forEach(l => fill(l, false));
    recalc(); partyInfo(); syncMode();
})();
</script>
@endpush
