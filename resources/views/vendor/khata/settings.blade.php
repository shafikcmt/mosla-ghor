@extends('vendor.khata.layout')
@section('title', 'সেটিং')
@section('heading', 'সেটিং')
@section('back', route('vendor.khata.home'))

@section('content')
@php $inp = 'w-full border border-gray-200 rounded-xl px-3 py-2.5 bg-white'; @endphp
<form method="POST" action="{{ route('vendor.khata.settings.save') }}" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 max-w-xl">
    @csrf
    <div>
        <p class="text-sm text-gray-500">দোকান</p>
        <p class="font-bold text-lg">{{ $vendor->shop_name }}</p>
        <p class="text-sm text-gray-600">{{ $vendor->phone }}</p>
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">ঠিকানা (ভাউচারে দেখাবে)</label>
        <input name="address" value="{{ old('address', $vendor->address) }}" maxlength="500" placeholder="যেমন: মৌলভীবাজার" class="{{ $inp }}">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1">ভাউচারের “শর্ত এবং নিয়ম”</label>
        <textarea name="terms" rows="3" maxlength="500" class="{{ $inp }}">{{ old('terms', $vendor->khataSetting('terms')) }}</textarea>
    </div>
    <label class="flex items-center gap-2 text-sm">
        <input type="hidden" name="show_balance" value="0">
        <input type="checkbox" name="show_balance" value="1" @checked($vendor->khataSetting('show_balance')) class="w-4 h-4 rounded">
        ভাউচারে পার্টির মোট ব্যালেন্স দেখান
    </label>
    <button class="w-full bg-[#0f7a3e] text-white font-bold py-3 rounded-xl">সংরক্ষণ করুন</button>
</form>

<form method="POST" action="{{ route('vendor.logout') }}" class="mt-4 max-w-xl">
    @csrf
    <button class="w-full border border-gray-300 text-gray-700 font-semibold py-3 rounded-xl">লগআউট</button>
</form>
@endsection
