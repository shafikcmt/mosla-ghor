@extends('vendor.khata.layout')
@section('title', 'হোম')
@section('subheading', 'দোকানের খাতা')

@section('content')
@php $tk = fn ($n) => ((float) $n < 0 ? '−' : '').'৳'.number_format(abs((float) $n), 0); @endphp

<div class="grid grid-cols-2 gap-3">
    <a href="{{ route('vendor.khata.parties.index', ['balance' => 'receivable']) }}" class="rounded-2xl bg-green-50 border border-green-200 p-4">
        <p class="text-2xl sm:text-3xl font-bold text-green-700 num">{{ $tk($dues['receivable']) }}</p>
        <p class="text-sm text-green-800 mt-1">পাওনা ↓ <span class="text-xs text-green-600">(পার্টির কাছে)</span></p>
    </a>
    <a href="{{ route('vendor.khata.parties.index', ['balance' => 'payable']) }}" class="rounded-2xl bg-red-50 border border-red-200 p-4">
        <p class="text-2xl sm:text-3xl font-bold text-red-600 num">{{ $tk($dues['payable']) }}</p>
        <p class="text-sm text-red-700 mt-1">বকেয়া ↑ <span class="text-xs text-red-500">(আপনার দেনা)</span></p>
    </a>
</div>

<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-3">
    @foreach([
        ['বিক্রয় ('.now()->format('F').')', $month['sales'], 'text-blue-700', route('vendor.khata.transactions', ['type' => 'sale'])],
        ['ক্রয় ('.now()->format('F').')', $month['purchase'], 'text-orange-600', route('vendor.khata.transactions', ['type' => 'purchase'])],
        ['খরচ ('.now()->format('F').')', $month['expense'], 'text-amber-600', route('vendor.khata.transactions', ['type' => 'expense'])],
        ['ক্যাশ ও ব্যাংক ব্যালেন্স', $cash, 'text-gray-900', route('vendor.khata.report')],
    ] as [$label, $value, $cls, $href])
    <a href="{{ $href }}" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-4">
        <p class="text-xl font-bold num {{ $cls }}">{{ $tk($value) }}</p>
        <p class="text-xs text-gray-500 mt-1">{{ $label }}</p>
    </a>
    @endforeach
</div>

<div class="mt-3 rounded-2xl bg-white border border-gray-100 shadow-sm p-4 flex flex-wrap items-center justify-between gap-2 text-sm">
    <span class="text-gray-500">আজ:</span>
    <span>বিক্রি <b class="num">{{ $tk($today['sales']) }}</b></span>
    <span>লাভ <b class="num {{ $today['net_profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $tk($today['net_profit']) }}</b></span>
    <span>ক্যাশ ইন <b class="num">{{ $tk($today['cash_in']) }}</b></span>
    <span>এই মাসের লাভ <b class="num {{ $month['net_profit'] >= 0 ? 'text-green-700' : 'text-red-600' }}">{{ $tk($month['net_profit']) }}</b></span>
</div>

{{-- Quick actions --}}
<div class="mt-4 grid grid-cols-4 gap-2 text-center text-xs sm:text-sm">
    @foreach([
        [route('vendor.khata.trade.create', 'sale'), 'বিক্রি', '🧾', 'bg-blue-600'],
        [route('vendor.khata.trade.create', 'purchase'), 'ক্রয়', '🛒', 'bg-orange-500'],
        [route('vendor.khata.amount.create', 'payment_in'), 'পেমেন্ট ইন', '⬇️', 'bg-green-600'],
        [route('vendor.khata.amount.create', 'expense'), 'খরচ', '💸', 'bg-amber-500'],
    ] as [$href, $label, $icon, $cls])
    <a href="{{ $href }}" class="rounded-2xl {{ $cls }} text-white font-semibold py-3 shadow-sm hover:brightness-95">
        <span class="block text-xl">{{ $icon }}</span>{{ $label }}
    </a>
    @endforeach
</div>

@if($counts['items'] === 0 || $counts['parties'] === 0)
<div class="mt-4 rounded-2xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900">
    <p class="font-bold mb-1">শুরু করুন 👋</p>
    <ol class="list-decimal pl-5 space-y-0.5">
        <li @class(['line-through text-amber-600' => $counts['items'] > 0])><a class="underline" href="{{ route('vendor.khata.items.create') }}">আপনার পণ্যগুলো (আইটেম) যোগ করুন</a> — দাম ও বর্তমান স্টকসহ।</li>
        <li @class(['line-through text-amber-600' => $counts['parties'] > 0])><a class="underline" href="{{ route('vendor.khata.parties.create') }}">গ্রাহক / সরবরাহকারী যোগ করুন</a> — আগের পাওনা/বকেয়া থাকলে সেটাও।</li>
        <li>তারপর “বিক্রি” বা “ক্রয়” দিয়ে লেনদেন লিখুন — স্টক ও হিসাব নিজে থেকে মিলবে।</li>
    </ol>
</div>
@endif

@if($lowStock->isNotEmpty())
<div class="mt-4 rounded-2xl bg-white border border-red-100 shadow-sm">
    <p class="px-4 pt-3 font-bold text-red-700 text-sm">⚠ স্টক কম</p>
    @foreach($lowStock as $item)
    <a href="{{ route('vendor.khata.items.show', $item) }}" class="flex justify-between px-4 py-2 text-sm hover:bg-gray-50">
        <span>{{ $item->name }}</span>
        <span class="font-semibold text-red-600 num">{{ rtrim(rtrim(number_format((float) $item->stock, 3, '.', ''), '0'), '.') }} {{ $item->unitLabel() }}</span>
    </a>
    @endforeach
</div>
@endif

<div class="mt-4 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-4 pt-3 pb-1">
        <p class="font-bold">সাম্প্রতিক লেনদেন</p>
        <a href="{{ route('vendor.khata.transactions') }}" class="text-sm text-[#0f7a3e] font-semibold">সব দেখুন →</a>
    </div>
    <div class="divide-y divide-gray-50">
        @forelse($recent as $tx)
            @include('vendor.khata._tx-row')
        @empty
            <p class="px-4 py-8 text-center text-gray-400 text-sm">এখনো কোনো লেনদেন নেই। নিচের ➕ বাটন থেকে শুরু করুন।</p>
        @endforelse
    </div>
</div>
@endsection
