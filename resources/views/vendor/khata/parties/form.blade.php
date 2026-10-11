@extends('vendor.khata.layout')
@section('title', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি')
@section('heading', $party->exists ? 'পার্টি সম্পাদনা' : 'নতুন পার্টি')
@section('back', $party->exists ? route('vendor.khata.parties.show', $party) : ($back ?: route('vendor.khata.parties.index')))

@section('content')
@php
    $inp  = 'w-full border border-gray-200 rounded-xl px-3 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $ob   = (float) $party->opening_balance;
    $side = old('opening_side', $ob < 0 ? 'payable' : 'receivable');
    $more = old('opening_amount', $ob) || old('address', $party->address) || old('note', $party->note);
@endphp
<form method="POST" action="{{ $party->exists ? route('vendor.khata.parties.update', $party) : route('vendor.khata.parties.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4">
    @csrf
    @if($party->exists) @method('PUT') @endif
    @if($back)<input type="hidden" name="back" value="{{ $back }}">@endif

    <div class="grid grid-cols-2 gap-2">
        @foreach(\App\Models\Khata\KhataParty::TYPES as $k => $l)
        <label class="cursor-pointer">
            <input type="radio" name="type" value="{{ $k }}" class="peer sr-only" @checked(old('type', $party->type) === $k)>
            <span class="block text-center font-semibold py-2.5 rounded-xl border border-gray-200 text-gray-600 peer-checked:bg-[#0f7a3e] peer-checked:text-white peer-checked:border-[#0f7a3e]">{{ $l }}</span>
        </label>
        @endforeach
    </div>

    <div>
        <label class="block text-sm font-semibold mb-1">নাম <span class="text-red-500">*</span></label>
        <input name="name" value="{{ old('name', $party->name) }}" required maxlength="150" @unless($party->exists) autofocus @endunless class="{{ $inp }}">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">ফোন নম্বর</label>
        <input type="tel" name="phone" value="{{ old('phone', $party->phone) }}" inputmode="tel" placeholder="01XXXXXXXXX" class="{{ $inp }}">
    </div>

    <details class="group rounded-xl border border-gray-100 bg-gray-50" @if($more) open @endif>
        <summary class="cursor-pointer list-none px-3 py-2.5 text-sm font-semibold text-[#0f7a3e] flex items-center justify-between">
            আগের বাকি / ঠিকানা (ঐচ্ছিক)
            <span class="transition group-open:rotate-180">⌄</span>
        </summary>
        <div class="px-3 pb-3 space-y-3">
            <div>
                <label class="block text-sm font-semibold mb-1">আগের হিসাব</label>
                <input type="number" name="opening_amount" step="0.01" min="0" inputmode="decimal" placeholder="৳ 0" value="{{ old('opening_amount', $ob ? abs($ob) : '') }}" class="{{ $inp }}">
                <div class="grid grid-cols-2 gap-2 mt-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="opening_side" value="receivable" class="peer sr-only" @checked($side === 'receivable')>
                        <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 bg-white peer-checked:bg-green-600 peer-checked:text-white peer-checked:border-green-600">পাওনা (সে দেবে)</span>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="opening_side" value="payable" class="peer sr-only" @checked($side === 'payable')>
                        <span class="block text-center text-sm font-semibold py-2 rounded-xl border border-gray-200 bg-white peer-checked:bg-red-600 peer-checked:text-white peer-checked:border-red-600">বকেয়া (আমি দেবো)</span>
                    </label>
                </div>
                <input type="hidden" name="opening_date" value="{{ old('opening_date', optional($party->opening_date)->toDateString() ?? now()->toDateString()) }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">ঠিকানা</label>
                <input name="address" value="{{ old('address', $party->address) }}" maxlength="300" class="{{ $inp }}">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">নোট</label>
                <input name="note" value="{{ old('note', $party->note) }}" maxlength="500" class="{{ $inp }}">
            </div>
        </div>
    </details>

    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl">{{ $party->exists ? 'সেভ করুন' : 'পার্টি যোগ করুন' }}</button>
</form>

@if($party->exists)
<form method="POST" action="{{ route('vendor.khata.parties.destroy', $party) }}" class="mt-4 text-center" onsubmit="return confirm('পার্টি মুছবেন?');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">পার্টি মুছুন</button>
</form>
@endif
@endsection
