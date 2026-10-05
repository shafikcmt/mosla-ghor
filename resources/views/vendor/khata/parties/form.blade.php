@extends('vendor.khata.layout')
@section('title', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি')
@section('heading', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি যোগ করুন')
@section('back', $party->exists ? route('vendor.khata.parties.show', $party) : ($back ?: route('vendor.khata.parties.index')))

@section('content')
@php
    $inp  = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $ob   = (float) $party->opening_balance;
    $side = old('opening_side', $ob < 0 ? 'payable' : 'receivable');
@endphp
<form method="POST" action="{{ $party->exists ? route('vendor.khata.parties.update', $party) : route('vendor.khata.parties.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 max-w-2xl">
    @csrf
    @if($party->exists) @method('PUT') @endif
    @if($back)<input type="hidden" name="back" value="{{ $back }}">@endif

    <div>
        <label class="block text-sm font-semibold mb-1">পার্টির নাম *</label>
        <input name="name" value="{{ old('name', $party->name) }}" required maxlength="150" autofocus class="{{ $inp }}">
    </div>
    <div class="grid grid-cols-2 gap-3">
        <div>
            <label class="block text-sm font-semibold mb-1">ফোন নম্বর</label>
            <input type="tel" name="phone" value="{{ old('phone', $party->phone) }}" inputmode="tel" placeholder="01XXXXXXXXX" class="{{ $inp }}">
        </div>
        <div>
            <label class="block text-sm font-semibold mb-1">পার্টি প্রকার</label>
            <div class="flex gap-2">
                @foreach(\App\Models\Khata\KhataParty::TYPES as $k => $l)
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="type" value="{{ $k }}" class="peer sr-only" @checked(old('type', $party->type) === $k)>
                    <span class="block text-center text-sm font-semibold py-2.5 rounded-xl border border-gray-200 peer-checked:bg-[#0f7a3e] peer-checked:text-white peer-checked:border-[#0f7a3e]">{{ $l }}</span>
                </label>
                @endforeach
            </div>
        </div>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">ঠিকানা</label>
        <input name="address" value="{{ old('address', $party->address) }}" maxlength="300" class="{{ $inp }}">
    </div>

    <div class="rounded-xl bg-gray-50 border border-gray-100 p-3">
        <p class="text-sm font-semibold mb-2">আগের হিসাব (ওপেনিং ব্যালেন্স)</p>
        <div class="grid grid-cols-2 gap-3">
            <input type="number" name="opening_amount" step="0.01" min="0" inputmode="decimal" placeholder="৳ 0" value="{{ old('opening_amount', $ob ? abs($ob) : '') }}" class="{{ $inp }}">
            <input type="date" name="opening_date" value="{{ old('opening_date', optional($party->opening_date)->toDateString() ?? now()->toDateString()) }}" class="{{ $inp }}">
        </div>
        <div class="flex gap-2 mt-2">
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="opening_side" value="receivable" class="peer sr-only" @checked($side === 'receivable')>
                <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600">পাওনা (সে দেবে)</span>
            </label>
            <label class="flex-1 cursor-pointer">
                <input type="radio" name="opening_side" value="payable" class="peer sr-only" @checked($side === 'payable')>
                <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600">বকেয়া (আমি দেব)</span>
            </label>
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">নোট</label>
        <input name="note" value="{{ old('note', $party->note) }}" maxlength="500" class="{{ $inp }}">
    </div>
    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3 rounded-xl">{{ $party->exists ? 'সেভ করুন' : 'নতুন পার্টি যোগ করুন' }}</button>
</form>

@if($party->exists)
<form method="POST" action="{{ route('vendor.khata.parties.destroy', $party) }}" class="mt-4" onsubmit="return confirm('পার্টি মুছবেন?');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">🗑 পার্টি মুছুন</button>
</form>
@endif
@endsection
