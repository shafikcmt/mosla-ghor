@extends('vendor.khata.layout')
@section('title', $item->exists ? 'আইটেম সম্পাদনা' : 'নতুন আইটেম')
@section('heading', $item->exists ? 'আইটেম সম্পাদনা' : 'নতুন আইটেম যোগ')
@section('back', $item->exists ? route('vendor.khata.items.show', $item) : route('vendor.khata.items.index'))

@section('content')
@php $inp = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]'; @endphp
<form method="POST" action="{{ $item->exists ? route('vendor.khata.items.update', $item) : route('vendor.khata.items.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 max-w-2xl">
    @csrf
    @if($item->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-semibold mb-1">আইটেমের নাম *</label>
        <input name="name" value="{{ old('name', $item->name) }}" required maxlength="150" autofocus placeholder="যেমন: জিরা, মসলা গুঁড়া" class="{{ $inp }}">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold mb-1">শ্রেণী</label>
            <input name="category" value="{{ old('category', $item->category) }}" list="kh-cats" maxlength="80" placeholder="যেমন: Masala" class="{{ $inp }}">
            <datalist id="kh-cats">@foreach($categories as $c)<option value="{{ $c }}">@endforeach</datalist>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">একক *</label>
            <select name="unit" class="{{ $inp }}">
                @foreach(\App\Models\Khata\KhataItem::UNITS as $k => $l)<option value="{{ $k }}" @selected(old('unit', $item->unit) === $k)>{{ $l }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">বিক্রয় মূল্য (৳)</label>
            <input type="number" name="sale_price" step="0.01" min="0" inputmode="decimal" value="{{ old('sale_price', $item->exists ? (float) $item->sale_price : '') }}" class="{{ $inp }}">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">ক্রয় মূল্য (৳)</label>
            <input type="number" name="purchase_price" step="0.01" min="0" inputmode="decimal" value="{{ old('purchase_price', $item->exists ? (float) $item->purchase_price : '') }}" class="{{ $inp }}">
            <p class="text-[11px] text-gray-400 mt-0.5">লাভ হিসাব করতে কাজে লাগে।</p>
        </div>
        @unless($item->exists)
        <div>
            <label class="block text-sm font-semibold mb-1">বর্তমান স্টক</label>
            <input type="number" name="opening_stock" step="0.001" min="0" inputmode="decimal" value="{{ old('opening_stock') }}" placeholder="0" class="{{ $inp }}">
        </div>
        @endunless
        <div>
            <label class="block text-sm font-semibold mb-1">স্টক কম সতর্কতা</label>
            <input type="number" name="low_stock_alert" step="0.001" min="0" inputmode="decimal" value="{{ old('low_stock_alert', $item->low_stock_alert !== null ? (float) $item->low_stock_alert : '') }}" placeholder="যেমন: 5" class="{{ $inp }}">
        </div>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">নোট</label>
        <input name="note" value="{{ old('note', $item->note) }}" maxlength="500" class="{{ $inp }}">
    </div>

    <div class="flex flex-wrap gap-2 pt-1">
        <button class="flex-1 bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3 rounded-xl">সেভ করুন</button>
        @unless($item->exists)
        <button name="add_another" value="1" class="flex-1 border-2 border-[#0f7a3e] text-[#0f7a3e] font-bold py-3 rounded-xl">সেভ করে আরেকটি যোগ</button>
        @endunless
    </div>
</form>

@if($item->exists)
<form method="POST" action="{{ route('vendor.khata.items.destroy', $item) }}" class="mt-4 max-w-2xl" onsubmit="return confirm('আইটেমটি মুছবেন?');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">🗑 আইটেম মুছুন</button>
</form>
@endif
@endsection
