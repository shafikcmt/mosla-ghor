@extends('admin.layout')
@section('title', 'নতুন ভেন্ডর')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← ভেন্ডর তালিকা</a>
    <h2 class="text-xl font-bold text-gray-800 mt-1">নতুন ভেন্ডর যোগ করুন</h2>
</div>

<form method="POST" action="{{ route('admin.vendors.store') }}" autocomplete="off"
      x-data="{ mode: '{{ old('password_mode', 'auto') }}' }" class="max-w-3xl space-y-5">
    @csrf

    {{-- Business info --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">ব্যবসার তথ্য</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ব্যবসা / দোকানের নাম <span class="text-red-500">*</span></label>
                <input type="text" name="shop_name" value="{{ old('shop_name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">মালিকের নাম <span class="text-red-500">*</span></label>
                <input type="text" name="owner_name" value="{{ old('owner_name') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ব্যবসার ধরন</label>
                <select name="business_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">— নির্বাচন করুন —</option>
                    @foreach($businessTypes as $bt)
                    <option value="{{ $bt }}" {{ old('business_type') === $bt ? 'selected' : '' }}>{{ $bt }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ট্রেড লাইসেন্স</label>
                <input type="text" name="trade_license" value="{{ old('trade_license') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">ঠিকানা</label>
                <input type="text" name="address" value="{{ old('address') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">জেলা</label>
                <input type="text" name="district" value="{{ old('district') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">শহর / এলাকা</label>
                <input type="text" name="city" value="{{ old('city') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">NID</label>
                <input type="text" name="nid" value="{{ old('nid') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
    </div>

    {{-- Login / credentials --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">লগইন তথ্য</h3>
        {{-- Decoy fields against browser autofill --}}
        <input type="text" name="fake_user" autocomplete="username" tabindex="-1" aria-hidden="true" style="display:none">
        <input type="password" name="fake_pass" autocomplete="new-password" tabindex="-1" aria-hidden="true" style="display:none">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ফোন <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone') }}" required
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ইমেইল <span class="text-gray-400">(login-এর জন্য; না দিলে অটো তৈরি হবে)</span></label>
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="off"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>

        <div class="mt-4">
            <label class="block text-xs font-medium text-gray-600 mb-2">পাসওয়ার্ড</label>
            <div class="flex gap-4 mb-3">
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="radio" name="password_mode" value="auto" x-model="mode" class="accent-[#14532d]"> অটো জেনারেট করুন
                </label>
                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="radio" name="password_mode" value="manual" x-model="mode" class="accent-[#14532d]"> নিজে দিন
                </label>
            </div>
            <p x-show="mode==='auto'" class="text-xs text-gray-400">একটি নিরাপদ পাসওয়ার্ড তৈরি হবে এবং তৈরির পর শুধু একবার দেখানো হবে।</p>
            <div x-show="mode==='manual'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="password" name="password" autocomplete="new-password" placeholder="পাসওয়ার্ড (৮+ অক্ষর)"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="পাসওয়ার্ড নিশ্চিত করুন"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
    </div>

    {{-- Status & commission --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-4">স্ট্যাটাস ও কমিশন</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">স্ট্যাটাস <span class="text-red-500">*</span></label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="pending" {{ old('status', 'pending') === 'pending' ? 'selected' : '' }}>পেন্ডিং</option>
                    <option value="approved" {{ old('status') === 'approved' ? 'selected' : '' }}>অনুমোদিত</option>
                    <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>স্থগিত</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন ধরন</label>
                <select name="commission_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="">ডিফল্ট</option>
                    <option value="percentage" {{ old('commission_type') === 'percentage' ? 'selected' : '' }}>শতাংশ (%)</option>
                    <option value="fixed" {{ old('commission_type') === 'fixed' ? 'selected' : '' }}>নির্দিষ্ট (৳)</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কমিশন মান</label>
                <input type="number" name="commission_value" value="{{ old('commission_value') }}" step="0.01" min="0"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
        <div class="mt-4">
            <label class="block text-xs font-medium text-gray-600 mb-1">অ্যাডমিন নোট</label>
            <textarea name="admin_note" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('admin_note') }}</textarea>
        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit" class="bg-[#14532d] text-white text-sm font-medium px-6 py-2.5 rounded-lg hover:bg-[#0d3520] transition-colors">ভেন্ডর তৈরি করুন</button>
        <a href="{{ route('admin.vendors.index') }}" class="text-sm text-gray-500 px-6 py-2.5 border border-gray-300 rounded-lg">বাতিল</a>
    </div>
</form>
@endsection
