@extends('vendor.khata.layout')
@section('title', $staff->exists ? 'স্টাফ সম্পাদনা' : 'নতুন স্টাফ')
@section('heading', $staff->exists ? 'স্টাফ সম্পাদনা' : 'নতুন স্টাফ')
@section('back', route('vendor.khata.staff.index'))

@section('content')
@php
    $inp = 'w-full border border-gray-200 rounded-xl px-3 py-3 text-base bg-white focus:outline-none focus:ring-2 focus:ring-[#0f7a3e]';
    $perms = (array) old('permissions', $staff->staff_permissions ?? []);
@endphp
<form method="POST" action="{{ $staff->exists ? route('vendor.khata.staff.update', $staff) : route('vendor.khata.staff.store') }}"
      class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4 space-y-4 lg:max-w-2xl">
    @csrf
    @if($staff->exists) @method('PUT') @endif

    <div>
        <label class="block text-sm font-semibold mb-1" for="name">নাম <span class="text-red-500">*</span></label>
        <input id="name" name="name" value="{{ old('name', $staff->name) }}" required maxlength="100" class="{{ $inp }}">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" for="phone">মোবাইল নম্বর (লগইন) <span class="text-red-500">*</span></label>
        <input id="phone" type="tel" name="phone" value="{{ old('phone', $staff->phone) }}" required inputmode="tel" placeholder="01XXXXXXXXX" class="{{ $inp }}">
    </div>
    <div>
        <label class="block text-sm font-semibold mb-1" for="password">পাসওয়ার্ড @unless($staff->exists)<span class="text-red-500">*</span>@endunless</label>
        <input id="password" type="text" name="password" autocomplete="new-password" minlength="6" maxlength="100" @unless($staff->exists) required @endunless
               placeholder="{{ $staff->exists ? 'বদলাতে চাইলে নতুন পাসওয়ার্ড দিন' : 'কমপক্ষে ৬ অক্ষর' }}" class="{{ $inp }}">
        <p class="text-xs text-gray-500 mt-1">স্টাফকে নম্বর ও পাসওয়ার্ড জানিয়ে দিন।</p>
    </div>

    <fieldset>
        <legend class="text-sm font-semibold mb-2">কোন কাজগুলো করতে পারবে?</legend>
        <div class="space-y-2">
            @foreach(\App\Support\KhataStaff::PERMISSIONS as $key => $label)
            <label class="flex items-center gap-3 rounded-xl border border-gray-200 px-3 py-2.5 cursor-pointer has-[:checked]:border-[#0f7a3e] has-[:checked]:bg-green-50">
                <input type="checkbox" name="permissions[]" value="{{ $key }}" @checked(in_array($key, $perms, true)) class="w-5 h-5 accent-[#0f7a3e]">
                <span class="text-sm">{{ $label }}</span>
            </label>
            @endforeach
        </div>
        <p class="text-xs text-gray-500 mt-2">সব স্টাফ হোম ও আইটেমের তালিকা দেখতে পারে, আর নিজের লেখা এন্ট্রি দেখতে পারে। সেটিং ও স্টাফ ব্যবস্থাপনা শুধু মালিকের।</p>
    </fieldset>

    @if($staff->exists)
    <label class="flex items-center gap-3">
        <input type="hidden" name="staff_active" value="0">
        <input type="checkbox" name="staff_active" value="1" @checked(old('staff_active', $staff->staff_active)) class="w-5 h-5 accent-[#0f7a3e]">
        <span class="text-sm font-semibold">অ্যাকাউন্ট চালু (বন্ধ করলে সাথে সাথে লগআউট হয়ে যাবে)</span>
    </label>
    @endif

    <button class="w-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white font-bold py-3.5 rounded-xl">{{ $staff->exists ? 'সেভ করুন' : 'স্টাফ যোগ করুন' }}</button>
</form>

@if($staff->exists)
<form method="POST" action="{{ route('vendor.khata.staff.destroy', $staff) }}" class="mt-4 text-center lg:max-w-2xl" onsubmit="return confirm('এই স্টাফকে সরাবেন? তার লেখা এন্ট্রিগুলো থাকবে।');">
    @csrf @method('DELETE')
    <button class="text-sm text-red-600 hover:underline">স্টাফ সরিয়ে দিন</button>
</form>
@endif
@endsection
