@extends('vendor.khata.layout')
@section('title', $item->exists ? 'আইটেম সম্পাদনা' : 'নতুন আইটেম')
@section('heading', $item->exists ? 'আইটেম সম্পাদনা' : 'নতুন আইটেম যোগ')
@section('back', $item->exists ? route('vendor.khata.items.show', $item) : route('vendor.khata.items.index'))

@section('content')
@php
    $inp  = 'w-full border border-gray-200 rounded-xl px-3 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $more = old('category', $item->category) || old('low_stock_alert', $item->low_stock_alert) || old('note', $item->note);
@endphp
<form method="POST" action="{{ $item->exists ? route('vendor.khata.items.update', $item) : route('vendor.khata.items.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-semibold mb-1">আইটেমের নাম <span class="text-red-500">*</span></label>
        <input name="name" value="{{ old('name', $item->name) }}" required maxlength="150" @unless($item->exists) autofocus @endunless placeholder="যেমন: জিরা" class="{{ $inp }}">
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold mb-1">বিক্রয় মূল্য</label>
            <input type="number" name="sale_price" step="0.01" min="0" inputmode="decimal" placeholder="৳" value="{{ old('sale_price', $item->exists ? (float) $item->sale_price : '') }}" class="{{ $inp }}">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">একক</label>
            <select name="unit" class="{{ $inp }}">
                @foreach(\App\Models\Khata\KhataItem::UNITS as $k => $l)<option value="{{ $k }}" @selected(old('unit', $item->unit) === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        @unless($item->exists)
        <div>
            <label class="block text-sm font-semibold mb-1">এখন কত স্টক আছে</label>
            <input type="number" name="opening_stock" step="0.001" min="0" inputmode="decimal" value="{{ old('opening_stock') }}" placeholder="0" class="{{ $inp }}">
        </div>
        @endunless
        <div>
            <label class="block text-sm font-semibold mb-1">ক্রয় মূল্য</label>
            <input type="number" name="purchase_price" step="0.01" min="0" inputmode="decimal" placeholder="৳" value="{{ old('purchase_price', $item->exists ? (float) $item->purchase_price : '') }}" class="{{ $inp }}">
        </div>
    </div>
    <p class="text-xs text-gray-400 -mt-2">ক্রয় মূল্য দিলে প্রতিটি বিক্রিতে কত লাভ হলো তা রিপোর্টে দেখাবে।</p>

    <details class="group rounded-xl border border-gray-100 bg-gray-50" @if($more) open @endif>
        <summary class="cursor-pointer list-none px-3 py-2.5 text-sm font-semibold text-[#0f7a3e] flex items-center justify-between">
            আরও বিস্তারিত (ঐচ্ছিক)
            <span class="transition group-open:rotate-180">⌄</span>
        </summary>
        <div class="px-3 pb-3 space-y-3">
            <div>
                <label class="block text-sm font-semibold mb-1">শ্রেণী</label>
                <input name="category" value="{{ old('category', $item->category) }}" list="kh-cats" maxlength="80" placeholder="যেমন: মসলা" class="{{ $inp }}">
                <datalist id="kh-cats">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">স্টক কম হলে জানাবে</label>
                <input type="number" name="low_stock_alert" step="0.001" min="0" inputmode="decimal" value="{{ old('low_stock_alert', $item->low_stock_alert !== null ? (float) $item->low_stock_alert : '') }}" placeholder="যেমন: 5" class="{{ $inp }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">নোট</label>
                <input name="note" value="{{ old('note', $item->note) }}" maxlength="500" class="{{ $inp }}">
            </div>
        </div>
    </details>

    <div class="flex gap-2">
        <button class="flex-1 bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl">সেভ করুন</button>
        @unless($item->exists)
        <button name="add_another" value="1" class="flex-1 border-2 border-[#0f7a3e] text-[#0f7a3e] font-bold py-3.5 rounded-xl">সেভ + আরেকটি</button>
        @endunless
    </div>
</form>

@if($item->exists)
<form method="POST" action="{{ route('vendor.khata.items.destroy', $item) }}" class="mt-4 text-center" onsubmit="return confirm('আইটেমটি মুছবেন?');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">আইটেম মুছুন</button>
</form>
@endif
@endsection
