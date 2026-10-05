@extends('vendor.khata.layout')
@php
    $titles = ['payment_in' => 'প্রাপ্ত টাকা যোগ করুন (পেমেন্ট ইন)', 'payment_out' => 'টাকা পরিশোধ (পেমেন্ট আউট)', 'expense' => 'খরচ যোগ করুন'];
    $inp = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $partyData = $parties->mapWithKeys(fn ($p) => [$p->id => $p->balance()]);
    $selParty = old('party_id', $partyId);
@endphp
@section('title', $titles[$type])
@section('heading', $titles[$type])
@section('back', $selParty ? route('vendor.khata.parties.show', $selParty) : route('vendor.khata.home'))

@section('content')
<form method="POST" action="{{ route('vendor.khata.amount.store', $type) }}" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 max-w-xl">
    @csrf
    @if($type === 'expense')
        <div>
            <label class="block text-sm font-semibold mb-1">খরচের খাত</label>
            <input name="category" value="{{ old('category') }}" list="kh-exp" maxlength="80" placeholder="যেমন: দোকান ভাড়া" class="{{ $inp }}">
            <datalist id="kh-exp">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            <div class="flex flex-wrap gap-1.5 mt-2">
                @foreach($categories->take(6) as $c)
                <button type="button" onclick="this.form.category.value = this.textContent" class="text-xs px-2.5 py-1 rounded-full bg-gray-100 text-gray-700">{{ $c }}</button>
                @endforeach
            </div>
        </div>
    @else
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="text-sm font-semibold">পার্টি *</label>
                <a href="{{ route('vendor.khata.parties.create', ['type' => $type === 'payment_in' ? 'customer' : 'supplier', 'back' => request()->getRequestUri()]) }}" class="text-xs font-semibold text-[#0f7a3e]">+ নতুন পার্টি</a>
            </div>
            <select name="party_id" id="kh-party" required class="{{ $inp }}">
                <option value="">— পার্টি বেছে নিন —</option>
                @foreach($parties as $p)<option value="{{ $p->id }}" @selected((string) $selParty === (string) $p->id)>{{ $p->name }}{{ $p->phone ? ' — '.$p->phone : '' }}</option>@endforeach
            </select>
            <p id="kh-bal" class="text-sm mt-1.5"></p>
        </div>
    @endif

    <div>
        <label class="block text-sm font-semibold mb-1">টাকার পরিমাণ *</label>
        <div class="flex gap-2">
            <input type="number" name="amount" id="kh-amount" value="{{ old('amount') }}" step="0.01" min="0.01" required inputmode="decimal" placeholder="৳ 0" class="{{ $inp }} text-2xl font-bold">
            @if($type !== 'expense')<button type="button" id="kh-all" class="shrink-0 text-sm font-semibold text-[#0f7a3e] border border-green-200 rounded-xl px-3 hidden">পুরো বাকি</button>@endif
        </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold mb-1">তারিখ</label>
            <input type="date" name="date" value="{{ old('date', now()->toDateString()) }}" required class="{{ $inp }}">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">মাধ্যম</label>
            <select name="payment_mode" class="{{ $inp }}">
                @foreach(['cash' => 'নগদ', 'bkash' => 'bKash', 'nagad' => 'Nagad', 'bank' => 'ব্যাংক'] as $k => $l)<option value="{{ $k }}" @selected(old('payment_mode', 'cash') === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">নোট</label>
        <input name="note" value="{{ old('note') }}" maxlength="500" class="{{ $inp }}">
    </div>
    <button class="w-full {{ $type === 'payment_in' ? 'bg-green-600' : ($type === 'payment_out' ? 'bg-red-600' : 'bg-amber-500') }} text-white font-bold py-3 rounded-xl">সেভ করুন</button>
</form>
@endsection

@if($type !== 'expense')
@push('scripts')
<script>
(function () {
    const BAL = @json($partyData), TYPE = @json($type);
    const sel = document.getElementById('kh-party'), out = document.getElementById('kh-bal'), all = document.getElementById('kh-all');
    function show() {
        const b = BAL[sel.value];
        if (b === undefined) { out.textContent = ''; all.classList.add('hidden'); return; }
        out.className = 'text-sm mt-1.5 ' + (b > 0 ? 'text-green-700' : b < 0 ? 'text-red-600' : 'text-gray-500');
        out.textContent = b > 0 ? 'পাওনা: ' + khTaka(b) : b < 0 ? 'বকেয়া: ' + khTaka(-b) : 'কোনো বাকি নেই';
        const due = TYPE === 'payment_in' ? b : -b;
        all.classList.toggle('hidden', !(due > 0));
        all.onclick = () => { document.getElementById('kh-amount').value = Math.round(due * 100) / 100; };
    }
    sel.addEventListener('change', show); show();
})();
</script>
@endpush
@endif
