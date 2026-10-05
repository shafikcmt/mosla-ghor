@extends('vendor.khata.layout')
@php
    $isSale = $type === 'sale';
    $title  = $isSale ? 'বিক্রি যোগ করুন' : 'ক্রয় যোগ করুন';
    $inp    = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $itemData = $items->mapWithKeys(fn ($i) => [$i->id => [
        'name' => $i->name, 'unit' => $i->unitLabel(), 'stock' => (float) $i->stock,
        'price' => (float) ($isSale ? $i->sale_price : $i->purchase_price),
    ]]);
    $partyData = $parties->mapWithKeys(fn ($p) => [$p->id => ['balance' => $p->balance(), 'phone' => $p->phone]]);
    $oldLines = old('lines', [['item_id' => '', 'quantity' => 1, 'price' => '']]);
    $selParty = old('party_id', $partyId);
@endphp
@section('title', $title)
@section('heading', $title)
@section('back', $selParty ? route('vendor.khata.parties.show', $selParty) : route('vendor.khata.home'))

@section('content')
<form method="POST" action="{{ route('vendor.khata.trade.store', $type) }}" id="kh-trade" class="space-y-3 max-w-3xl">
    @csrf
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 grid grid-cols-2 gap-3">
        <div>
            <p class="text-xs text-gray-500">{{ $isSale ? 'চালান নম্বর' : 'ক্রয় নম্বর' }}</p>
            <p class="text-xl font-bold num">#{{ $number }}</p>
        </div>
        <div>
            <label class="text-xs text-gray-500 block">তারিখ</label>
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="border border-gray-200 rounded-lg px-2 py-1.5 w-full">
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
                <div class="grid grid-cols-3 gap-2 mt-2 items-center">
                    <label class="text-xs text-gray-500">পরিমাণ <span class="kh-unit"></span>
                        <input type="number" name="lines[{{ $i }}][quantity]" value="{{ $l['quantity'] ?? 1 }}" step="0.001" min="0.001" required inputmode="decimal" class="kh-qty w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
                    </label>
                    <label class="text-xs text-gray-500">মূল্য (৳)
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

    {{-- Totals --}}
    <div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-2.5 text-sm">
        <div class="flex justify-between"><span class="text-gray-500">উপমোট</span><span id="kh-sub" class="font-semibold num">৳0</span></div>
        <div class="flex items-center justify-between gap-2">
            <span class="text-gray-500">ডিসকাউন্ট</span>
            <input type="number" name="discount" id="kh-disc" value="{{ old('discount') }}" step="0.01" min="0" placeholder="0" class="w-32 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
        </div>
        <div class="flex items-center justify-between gap-2">
            <input name="extra_label" value="{{ old('extra_label') }}" maxlength="60" placeholder="অতিরিক্ত চার্জ (যেমন: লেবার ভাড়া)" class="flex-1 min-w-0 border border-gray-200 rounded-lg px-2 py-1.5">
            <input type="number" name="extra_charge" id="kh-extra" value="{{ old('extra_charge') }}" step="0.01" min="0" placeholder="0" class="w-32 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
        </div>
        <div class="flex justify-between border-t pt-2.5 text-lg"><span class="font-bold">মোট</span><span id="kh-grand" class="font-bold num">৳0</span></div>
        <div class="flex items-center justify-between gap-2">
            <span class="font-semibold">{{ $isSale ? 'প্রাপ্ত টাকা' : 'পরিশোধিত টাকা' }}</span>
            <div class="flex items-center gap-2">
                <button type="button" id="kh-full" class="text-xs font-semibold text-[#0f7a3e] border border-green-200 rounded-lg px-2 py-1.5">পুরো টাকা</button>
                <input type="number" name="paid" id="kh-paid" value="{{ old('paid') }}" step="0.01" min="0" placeholder="0" class="w-32 border border-gray-200 rounded-lg px-2 py-1.5 text-right">
            </div>
        </div>
        <div class="flex items-center justify-between gap-2">
            <span class="text-gray-500">পেমেন্ট মাধ্যম</span>
            <select name="payment_mode" class="border border-gray-200 rounded-lg px-2 py-1.5">
                @foreach(['cash' => 'নগদ', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'ব্যাংক'] as $k => $l)<option value="{{ $k }}" @selected(old('payment_mode', 'cash') === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div class="flex justify-between"><span class="font-semibold">বাকি</span><span id="kh-due" class="font-bold text-red-600 num">৳0</span></div>
        <input name="note" value="{{ old('note') }}" maxlength="500" placeholder="নোট (ঐচ্ছিক)" class="w-full border border-gray-200 rounded-lg px-3 py-2">
    </div>

    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl text-lg shadow">সেভ করুন</button>
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
        <div class="grid grid-cols-3 gap-2 mt-2 items-center">
            <label class="text-xs text-gray-500">পরিমাণ <span class="kh-unit"></span>
                <input type="number" name="lines[__I__][quantity]" value="1" step="0.001" min="0.001" required inputmode="decimal" class="kh-qty w-full border border-gray-200 rounded-lg px-2 py-2 bg-white text-base text-gray-800">
            </label>
            <label class="text-xs text-gray-500">মূল্য (৳)
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

    function fill(line, setPrice) {
        const it = ITEMS[line.querySelector('.kh-item').value];
        line.querySelector('.kh-unit').textContent = it ? '(' + it.unit + ')' : '';
        const price = line.querySelector('.kh-price');
        if (it && (setPrice || !price.value)) price.value = it.price || '';
        const st = line.querySelector('.kh-stock');
        if (!it) { st.textContent = ''; return; }
        const left = it.stock - (IS_SALE ? num(line.querySelector('.kh-qty')) : -num(line.querySelector('.kh-qty')));
        st.textContent = 'স্টক: ' + q3(it.stock) + ' ' + it.unit + (IS_SALE ? ' → বিক্রির পর ' + q3(left) : '');
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
    document.getElementById('kh-trade').addEventListener('input', recalc);
    document.getElementById('kh-full').addEventListener('click', () => { document.getElementById('kh-paid').value = Math.round(recalc() * 100) / 100; recalc(); });
    document.getElementById('kh-party').addEventListener('change', partyInfo);

    box.querySelectorAll('.kh-line').forEach(l => fill(l, false));
    recalc(); partyInfo();
})();
</script>
@endpush
