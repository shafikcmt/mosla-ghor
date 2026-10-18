@extends('vendor.khata.layout')
@section('title', 'হোম')
@section('subheading', 'দোকানের খাতা')

@section('content')
@php
    $tk = fn ($n) => ((float) $n < 0 ? '−' : '').'৳'.number_format(abs((float) $n), 0);
    $q3 = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');
    $isEmpty = $counts['items'] === 0 && $counts['parties'] === 0 && $recent->isEmpty();
    $hasToday = $today['sales'] > 0 || $today['cash_in'] > 0 || $today['expense'] > 0;
    // Shop staff see totals and reports only when the owner allowed them.
    $staff    = auth()->user()->isStaff();
    $can      = fn ($p) => \App\Support\KhataStaff::can(auth()->user(), $p);
@endphp

<div class="lg:grid lg:grid-cols-2 lg:gap-6 lg:items-start">
<div>
{{-- Balance --}}
@if($can('reports'))
<div class="rounded-3xl bg-gradient-to-br from-[#0f7a3e] to-[#15994f] text-white p-5 shadow-md">
    <div class="grid grid-cols-2 divide-x divide-white/25">
        <a href="{{ route('vendor.khata.parties.index', ['balance' => 'receivable']) }}" class="pr-4">
            <p class="text-sm text-green-100">পাওনা <span class="text-xs">(পাবো)</span></p>
            <p class="text-2xl sm:text-3xl font-bold num mt-0.5">{{ $tk($dues['receivable']) }}</p>
        </a>
        <a href="{{ route('vendor.khata.parties.index', ['balance' => 'payable']) }}" class="pl-4">
            <p class="text-sm text-green-100">বকেয়া <span class="text-xs">(দেবো)</span></p>
            <p class="text-2xl sm:text-3xl font-bold num mt-0.5">{{ $tk($dues['payable']) }}</p>
        </a>
    </div>
    <div class="mt-4 pt-3 border-t border-white/20 flex items-center justify-between text-sm">
        <span class="text-green-100">ক্যাশ ও ব্যাংক</span>
        <span class="font-bold num">{{ $tk($cash) }}</span>
    </div>
</div>
@else
<div class="rounded-3xl bg-gradient-to-br from-[#0f7a3e] to-[#15994f] text-white p-5 shadow-md">
    <p class="text-sm text-green-100">স্বাগতম</p>
    <p class="text-xl font-bold">{{ auth()->user()->name }}</p>
    <p class="text-xs text-green-100 mt-1">{{ $vendor->shop_name }} — স্টাফ</p>
</div>
@endif

{{-- Quick actions --}}
<div class="mt-4 grid grid-cols-4 gap-2 text-center text-xs font-semibold text-gray-700">
    @foreach([
        [route('vendor.khata.trade.create', 'sale'), 'বিক্রি', 'M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-3 2-3-2-3 2V5a2 2 0 012-2z', 'bg-blue-600', 'sale'],
        [route('vendor.khata.trade.create', 'purchase'), 'ক্রয়', 'M3 3h2l2.4 12.2a2 2 0 002 1.8h8.2a2 2 0 002-1.6L21 8H6M9 21a1 1 0 100-2 1 1 0 000 2zm9 0a1 1 0 100-2 1 1 0 000 2z', 'bg-orange-500', 'purchase'],
        [route('vendor.khata.amount.create', 'payment_in'), 'টাকা পেলাম', 'M12 4v12m0 0l-4-4m4 4l4-4M5 20h14', 'bg-green-600', 'payment'],
        [route('vendor.khata.amount.create', 'expense'), 'খরচ', 'M12 8c-2 0-3 .9-3 2s1 2 3 2 3 .9 3 2-1 2-3 2m0-8V6m0 10v2M4 6h16v12H4z', 'bg-amber-500', 'expense'],
    ] as [$href, $label, $path, $cls, $perm])
    @continue(! $can($perm))
    <a href="{{ $href }}" class="flex flex-col items-center gap-1.5">
        <span class="w-14 h-14 rounded-2xl {{ $cls }} text-white flex items-center justify-center shadow-sm hover:brightness-110">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
        </span>
        {{ $label }}
    </a>
    @endforeach
</div>

@if(! $staff && ($counts['items'] === 0 || $counts['parties'] === 0))
{{-- Getting started: two clear steps --}}
<div class="mt-5 rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
    <p class="font-bold">শুরু করুন 👋</p>
    <p class="text-sm text-gray-500 mt-0.5">দুইটা কাজ করলেই খাতা তৈরি — তারপর বিক্রি লিখলে স্টক ও বাকি নিজে থেকে হিসাব হবে।</p>
    <div class="mt-3 space-y-2">
        @foreach([
            [$counts['items'] > 0, route('vendor.khata.items.create'), '১', 'পণ্য যোগ করুন', 'নাম, দাম আর এখন কত স্টক আছে'],
            [$counts['parties'] > 0, route('vendor.khata.parties.create'), '২', 'গ্রাহক / সরবরাহকারী যোগ করুন', 'নাম আর ফোন — আগের বাকি থাকলে সেটাও'],
        ] as [$done, $href, $n, $label, $hint])
        <a href="{{ $href }}" class="flex items-center gap-3 rounded-xl border {{ $done ? 'border-green-200 bg-green-50' : 'border-gray-200 hover:border-[#0f7a3e]' }} p-3">
            <span class="shrink-0 w-9 h-9 rounded-full {{ $done ? 'bg-green-600 text-white' : 'bg-gray-100 text-gray-600' }} font-bold flex items-center justify-center">{{ $done ? '✓' : $n }}</span>
            <span class="flex-1 min-w-0">
                <span class="block font-semibold {{ $done ? 'text-green-700' : 'text-gray-800' }}">{{ $label }}</span>
                <span class="block text-xs text-gray-500">{{ $hint }}</span>
            </span>
            @unless($done)<span class="text-[#0f7a3e] text-xl">›</span>@endunless
        </a>
        @endforeach
    </div>
</div>
@endif

@if(! $isEmpty && $can('reports'))
{{-- This month --}}
<div class="mt-5 rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
    <div class="flex items-center justify-between">
        <p class="font-bold">এই মাস</p>
        <a href="{{ route('vendor.khata.report') }}" class="text-sm font-semibold text-[#0f7a3e]">রিপোর্ট →</a>
    </div>
    <div class="grid grid-cols-3 gap-2 mt-3 text-center">
        <a href="{{ route('vendor.khata.transactions', ['type' => 'sale']) }}" class="rounded-xl bg-blue-50 py-2.5">
            <p class="text-xs text-blue-700">বিক্রি</p><p class="font-bold num text-blue-800">{{ $tk($month['sales']) }}</p>
        </a>
        <a href="{{ route('vendor.khata.transactions', ['type' => 'purchase']) }}" class="rounded-xl bg-orange-50 py-2.5">
            <p class="text-xs text-orange-700">ক্রয়</p><p class="font-bold num text-orange-800">{{ $tk($month['purchase']) }}</p>
        </a>
        <a href="{{ route('vendor.khata.transactions', ['type' => 'expense']) }}" class="rounded-xl bg-amber-50 py-2.5">
            <p class="text-xs text-amber-700">খরচ</p><p class="font-bold num text-amber-800">{{ $tk($month['expense']) }}</p>
        </a>
    </div>
    <div class="flex items-center justify-between mt-3 text-sm">
        <span class="text-gray-500">লাভ (এই মাস)</span>
        <span class="font-bold num {{ $month['net_profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $tk($month['net_profit']) }}</span>
    </div>
    @if($hasToday)
    <div class="flex items-center justify-between mt-1 text-sm">
        <span class="text-gray-500">আজকের বিক্রি</span>
        <span class="font-semibold num">{{ $tk($today['sales']) }}</span>
    </div>
    @endif
</div>
@endif
</div>

<div class="lg:space-y-4">
@if($lowStock->isNotEmpty())
<div class="mt-4 lg:mt-0 rounded-2xl bg-white border border-red-100 shadow-sm">
    <p class="px-4 pt-3 font-bold text-red-700 text-sm">⚠ স্টক কম</p>
    @foreach($lowStock as $item)
    <a href="{{ route('vendor.khata.items.show', $item) }}" class="flex justify-between px-4 py-2 text-sm hover:bg-gray-50">
        <span>{{ $item->name }}</span>
        <span class="font-semibold text-red-600 num">{{ $item->stockLabel() }}</span>
    </a>
    @endforeach
</div>
@endif

@if($recent->isNotEmpty())
<div class="mt-4 lg:mt-0 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-4 pt-3 pb-1">
        <p class="font-bold">সাম্প্রতিক লেনদেন</p>
        @if($can('ledger'))<a href="{{ route('vendor.khata.transactions') }}" class="text-sm text-[#0f7a3e] font-semibold">সব দেখুন →</a>@endif
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($recent as $tx)
            @include('vendor.khata._tx-row')
        @endforeach
    </div>
</div>
@else
{{-- Desktop only: fill the right column until the first transaction exists --}}
<div class="hidden lg:block rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-4 pt-3 pb-1">
        <p class="font-bold">সাম্প্রতিক লেনদেন</p>
        <a href="{{ route('vendor.khata.transactions') }}" class="text-sm text-[#0f7a3e] font-semibold">সব দেখুন →</a>
    </div>
    <div class="px-6 py-10 text-center">
        <p class="text-4xl">🧾</p>
        <p class="mt-2 font-semibold text-gray-700">এখনো কোনো লেনদেন নেই</p>
        <p class="mt-1 text-sm text-gray-500">প্রথম বিক্রি বা ক্রয় লিখলে এখানে দেখাবে।</p>
        <div class="mt-4 flex justify-center gap-2">
            <a href="{{ route('vendor.khata.trade.create', 'sale') }}" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 rounded-xl">+ প্রথম বিক্রি</a>
            <a href="{{ route('vendor.khata.trade.create', 'purchase') }}" class="bg-orange-500 hover:bg-orange-600 text-white text-sm font-semibold px-4 py-2.5 rounded-xl">+ প্রথম ক্রয়</a>
        </div>
    </div>
    <div class="border-t border-gray-100 bg-gray-50 px-5 py-4">
        <p class="text-sm font-bold text-gray-700">খাতা যেভাবে কাজ করে</p>
        <ul class="mt-2 space-y-1.5 text-sm text-gray-600">
            <li>📦 <b>বিক্রি</b> লিখলে স্টক কমে, <b>ক্রয়</b> লিখলে স্টক বাড়ে।</li>
            <li>💰 পুরো টাকা না পেলে বাকিটা পার্টির <b>পাওনা</b> হিসেবে জমা থাকে।</li>
            <li>📲 প্রতিটি চালান WhatsApp-এ পাঠাতে বা প্রিন্ট করতে পারবেন।</li>
            <li>📊 দৈনিক, মাসিক ও বাৎসরিক লাভ-ক্ষতি <b>রিপোর্ট</b>-এ দেখুন।</li>
        </ul>
    </div>
</div>
@endif
</div>
</div>
@endsection
