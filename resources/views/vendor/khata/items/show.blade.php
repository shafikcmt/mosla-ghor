@extends('vendor.khata.layout')
@section('title', $item->name)
@section('heading', $item->name)
@section('subheading', ($item->category ? $item->category.' · ' : '').'একক: '.$item->unitLabel())
@section('back', route('vendor.khata.items.index'))

@section('content')
@php
    $q3 = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    $tk = fn ($n) => '৳'.number_format((float) $n, 0);
    $inp = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white';
@endphp

<div class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
    <p class="text-sm text-gray-500">বর্তমান স্টক</p>
    <p class="text-3xl font-bold num {{ (float) $item->stock <= 0 ? 'text-red-600' : 'text-gray-900' }}">{{ $q3($item->stock) }} <span class="text-lg font-semibold">{{ $item->unitLabel() }}</span></p>
    <div class="grid grid-cols-3 gap-2 mt-3 text-sm">
        <div><p class="text-gray-400 text-xs">বিক্রয়</p><p class="font-bold num">{{ $tk($stats->sold ?? 0) }}</p></div>
        <div><p class="text-gray-400 text-xs">ক্রয়</p><p class="font-bold num">{{ $tk($stats->bought ?? 0) }}</p></div>
        <div><p class="text-gray-400 text-xs">স্টক মূল্য</p><p class="font-bold num">{{ $tk($item->stockValue()) }}</p></div>
    </div>
    <div class="grid grid-cols-2 gap-2 mt-3 text-sm text-gray-600">
        <p>বিক্রয় মূল্য: <b class="num">{{ $tk($item->sale_price) }}</b></p>
        <p>ক্রয় মূল্য: <b class="num">{{ $tk($item->purchase_price) }}</b></p>
    </div>
    <a href="{{ route('vendor.khata.items.edit', $item) }}" class="inline-block mt-3 text-sm text-[#0f7a3e] font-semibold">✎ সম্পাদনা</a>
</div>

<div class="grid grid-cols-2 gap-2 mt-3">
    <button type="button" onclick="khAdjust('in')" class="bg-[#0f7a3e] text-white font-bold py-3 rounded-xl">+ স্টক যোগ করুন</button>
    <button type="button" onclick="khAdjust('out')" class="bg-red-600 text-white font-bold py-3 rounded-xl">− স্টক কমান</button>
</div>

<div id="kh-adjust" class="hidden mt-3 rounded-2xl bg-white border-2 border-green-200 p-4">
    <form method="POST" action="{{ route('vendor.khata.items.adjust', $item) }}" class="space-y-3">
        @csrf
        <input type="hidden" name="direction" id="kh-dir" value="in">
        <p class="font-bold" id="kh-adjust-title">স্টক যোগ করুন</p>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-semibold mb-1">পরিমাণ ({{ $item->unitLabel() }}) *</label>
                <input type="number" name="quantity" step="0.001" min="0.001" required inputmode="decimal" class="{{ $inp }}">
                <p class="text-[11px] text-gray-400 mt-0.5">বর্তমান স্টক: {{ $q3($item->stock) }}</p>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">তারিখ</label>
                <input type="date" name="date" value="{{ now()->toDateString() }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">একক মূল্য (৳)</label>
                <input type="number" name="price" step="0.01" min="0" value="{{ (float) $item->purchase_price }}" class="{{ $inp }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">বিবরণ</label>
                <input name="note" maxlength="300" placeholder="যেমন: নষ্ট, গণনায় সংশোধন" class="{{ $inp }}">
            </div>
        </div>
        <button class="w-full bg-[#0f7a3e] text-white font-bold py-3 rounded-xl">সেভ করুন</button>
        <p class="text-[11px] text-gray-400">সরবরাহকারী থেকে কেনা হলে “ক্রয়” এন্ট্রি দিন — তাতে বকেয়াও হিসাব হবে।</p>
    </form>
</div>

<div class="mt-4 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <p class="px-4 pt-3 pb-1 font-bold">পণ্যের কার্যকলাপ</p>
    <div class="divide-y divide-gray-50">
        @forelse($lines as $line)
        @php $t = $line->transaction; $in = in_array($t?->type, ['purchase', 'stock_in'], true); @endphp
        <a href="{{ $t ? route('vendor.khata.tx.show', $t) : '#' }}" class="flex items-center justify-between px-4 py-3 hover:bg-gray-50">
            <div>
                <p class="font-semibold">{{ $t?->typeLabel() }}{{ $t?->number ? ' #'.$t->number : '' }} @if($t?->party)<span class="text-gray-500 font-normal">— {{ $t->party->name }}</span>@endif</p>
                <p class="text-xs text-gray-400">{{ $t?->date->format('d M Y') }}@if($t?->note) · {{ $t->note }}@endif</p>
            </div>
            <div class="text-right">
                <p class="font-bold num {{ $in ? 'text-green-700' : 'text-red-600' }}">{{ $in ? '+' : '−' }}{{ $q3($line->quantity) }}</p>
                <p class="text-xs text-gray-400 num">৳{{ number_format((float) $line->total, 0) }}</p>
            </div>
        </a>
        @empty
        <p class="px-4 py-8 text-center text-gray-400 text-sm">কোনো লেনদেন নেই — এই আইটেমের কার্যকলাপ এখনো রেকর্ড করা হয়নি।</p>
        @endforelse
    </div>
</div>
<div class="mt-3">{{ $lines->links() }}</div>
@endsection

@push('scripts')
<script>
function khAdjust(dir) {
    const box = document.getElementById('kh-adjust');
    document.getElementById('kh-dir').value = dir;
    document.getElementById('kh-adjust-title').textContent = dir === 'in' ? 'স্টক যোগ করুন' : 'স্টক কমান';
    box.classList.remove('hidden');
    box.className = box.className.replace(/border-(green|red)-200/, dir === 'in' ? 'border-green-200' : 'border-red-200');
    box.querySelector('[name=quantity]').focus();
}
</script>
@endpush
