<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f7a3e">
    <title>@yield('title', 'দোকানের খাতা') — {{ $vendor->shop_name ?? 'খাতা' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Hind Siliguri', system-ui, sans-serif; -webkit-tap-highlight-color: transparent; }
        .num { font-variant-numeric: tabular-nums; }
        [x-cloak] { display: none !important; }
        @media print { .no-print { display: none !important; } body { background: #fff !important; } }
    </style>
    @stack('head')
</head>
<body class="bg-[#f3f6f4] text-gray-800 min-h-screen">
@php
    $khNav = [
        ['vendor.khata.home', 'হোম', 'M3 12l9-9 9 9M5 10v10h5v-6h4v6h5V10'],
        ['vendor.khata.transactions', 'লেনদেন', 'M4 6h16M4 12h16M4 18h10'],
        ['vendor.khata.parties.index', 'পার্টি', 'M17 20h5v-2a4 4 0 00-5-3.87M9 20H4v-2a4 4 0 015-3.87m6-4.13a4 4 0 11-8 0 4 4 0 018 0zm6 2a3 3 0 11-6 0 3 3 0 016 0z'],
        ['vendor.khata.items.index', 'ইনভেন্টরি', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
    ];
    $khActive = fn ($r) => request()->routeIs(str_replace('.index', '.*', $r)) || request()->routeIs($r);
@endphp

{{-- Top bar --}}
<header class="no-print sticky top-0 z-30 bg-[#0f7a3e] text-white shadow">
    <div class="max-w-3xl mx-auto px-4 h-14 flex items-center gap-3">
        @hasSection('back')
            <a href="@yield('back')" class="-ml-1 p-1.5 rounded-full hover:bg-white/10" aria-label="পেছনে">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
            </a>
        @endif
        <div class="min-w-0 flex-1">
            <p class="font-bold text-lg leading-tight truncate">@yield('heading', $vendor->shop_name)</p>
            @hasSection('subheading')<p class="text-xs text-green-100 truncate">@yield('subheading')</p>@endif
        </div>
        @unless($vendor->isInventoryOnly())
            <a href="{{ route('vendor.dashboard') }}" class="hidden sm:inline text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-full">মূল প্যানেল</a>
        @endunless
        <a href="{{ route('vendor.khata.report') }}" class="flex items-center gap-1 px-2.5 py-1.5 rounded-full {{ request()->routeIs('vendor.khata.report') ? 'bg-white text-[#0f7a3e]' : 'hover:bg-white/10' }}" aria-label="রিপোর্ট">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6m6 0h6m-6 0V9a2 2 0 012-2h2a2 2 0 012 2v10m6 0v-3a2 2 0 00-2-2h-2a2 2 0 00-2 2v3"/></svg>
            <span class="text-sm font-semibold hidden sm:inline">রিপোর্ট</span>
        </a>
        <a href="{{ route('vendor.khata.settings') }}" class="p-1.5 rounded-full hover:bg-white/10" aria-label="সেটিং">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.3 4.3c.4-1.7 2.9-1.7 3.4 0a1.7 1.7 0 002.6 1.1c1.5-.9 3.3.8 2.4 2.4a1.7 1.7 0 001 2.5c1.8.5 1.8 3 0 3.4a1.7 1.7 0 00-1 2.6c.9 1.5-.9 3.3-2.4 2.4a1.7 1.7 0 00-2.6 1c-.4 1.8-2.9 1.8-3.4 0a1.7 1.7 0 00-2.5-1c-1.6.9-3.3-.9-2.4-2.4a1.7 1.7 0 00-1.1-2.6c-1.7-.4-1.7-2.9 0-3.4a1.7 1.7 0 001.1-2.5c-.9-1.6.8-3.3 2.4-2.4 1 .6 2.3.1 2.5-1.1z"/><circle cx="12" cy="12" r="3"/></svg>
        </a>
        <form method="POST" action="{{ route('vendor.logout') }}" class="hidden sm:block">
            @csrf
            <button class="text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-full">লগআউট</button>
        </form>
    </div>
</header>

<main class="max-w-3xl mx-auto px-3 sm:px-4 pt-4 pb-28">
    @if(session('success'))
        <div class="no-print mb-3 flex items-start gap-2 bg-green-600 text-white text-sm font-medium rounded-xl px-4 py-3 shadow" role="status">
            <span>✓</span><span>{{ session('success') }}</span>
        </div>
    @endif
    @if(session('error'))
        <div class="no-print mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3" role="alert">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="no-print mb-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3" role="alert">
            @foreach($errors->all() as $e)<p>• {{ $e }}</p>@endforeach
        </div>
    @endif

    @yield('content')
</main>

{{-- Quick actions sheet --}}
<div id="kh-sheet" class="no-print fixed inset-0 z-40 hidden" aria-hidden="true">
    <div class="absolute inset-0 bg-black/40" onclick="khSheet(false)"></div>
    <div class="absolute inset-x-0 bottom-0 bg-white rounded-t-3xl p-5 pb-8 max-w-3xl mx-auto">
        <div class="w-10 h-1 bg-gray-300 rounded-full mx-auto mb-4"></div>
        <p class="font-bold text-gray-800 mb-4">কী লিখবেন?</p>
        <div class="grid grid-cols-4 gap-3 text-center text-[13px]">
            @foreach([
                [route('vendor.khata.trade.create', 'sale'), 'বিক্রি', 'M9 14l2 2 4-4M7 3h10a2 2 0 012 2v16l-3-2-3 2-3-2-3 2V5a2 2 0 012-2z', 'bg-blue-600'],
                [route('vendor.khata.trade.create', 'purchase'), 'ক্রয়', 'M3 3h2l2.4 12.2a2 2 0 002 1.8h8.2a2 2 0 002-1.6L21 8H6M9 21a1 1 0 100-2 1 1 0 000 2zm9 0a1 1 0 100-2 1 1 0 000 2z', 'bg-orange-500'],
                [route('vendor.khata.amount.create', 'payment_in'), 'টাকা পেলাম', 'M12 4v12m0 0l-4-4m4 4l4-4M5 20h14', 'bg-green-600'],
                [route('vendor.khata.amount.create', 'payment_out'), 'টাকা দিলাম', 'M12 20V8m0 0l-4 4m4-4l4 4M5 4h14', 'bg-red-500'],
                [route('vendor.khata.amount.create', 'expense'), 'খরচ', 'M12 8c-2 0-3 .9-3 2s1 2 3 2 3 .9 3 2-1 2-3 2m0-8V6m0 10v2M4 6h16v12H4z', 'bg-amber-500'],
                [route('vendor.khata.items.create'), 'নতুন আইটেম', 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'bg-purple-600'],
                [route('vendor.khata.parties.create'), 'নতুন পার্টি', 'M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zm10-3v6m3-3h-6', 'bg-teal-600'],
            ] as [$href, $label, $path, $cls])
            <a href="{{ $href }}" class="group flex flex-col items-center gap-1.5 font-semibold text-gray-700">
                <span class="w-14 h-14 rounded-2xl {{ $cls }} text-white flex items-center justify-center shadow-sm group-hover:brightness-110">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                </span>
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>
</div>

{{-- Bottom nav --}}
<nav class="no-print fixed bottom-0 inset-x-0 z-30 bg-white border-t border-gray-200 shadow-[0_-2px_12px_rgba(0,0,0,.06)]" style="padding-bottom: env(safe-area-inset-bottom);">
    <div class="max-w-3xl mx-auto grid grid-cols-5 relative">
        @foreach($khNav as $i => [$route, $label, $path])
            @if($i === 2)
            <div class="flex items-start justify-center">
                <button type="button" onclick="khSheet(true)" aria-label="নতুন এন্ট্রি"
                        class="-mt-6 w-14 h-14 rounded-full bg-[#0f7a3e] hover:bg-[#0c6533] text-white shadow-lg flex items-center justify-center ring-4 ring-white">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 5v14M5 12h14"/></svg>
                </button>
            </div>
            @endif
            <a href="{{ route($route) }}" class="flex flex-col items-center justify-center gap-0.5 py-2 min-h-[58px] text-[11px] font-semibold {{ $khActive($route) ? 'text-[#0f7a3e]' : 'text-gray-500' }}">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                {{ $label }}
            </a>
        @endforeach
    </div>
</nav>

<script>
    function khSheet(open) {
        const s = document.getElementById('kh-sheet');
        s.classList.toggle('hidden', !open);
        document.body.style.overflow = open ? 'hidden' : '';
    }
    document.addEventListener('keydown', e => { if (e.key === 'Escape') khSheet(false); });
    window.khTaka = n => '৳' + (Math.round(n * 100) / 100).toLocaleString('en-IN', { maximumFractionDigits: 2 });
</script>
@stack('scripts')
</body>
</html>
