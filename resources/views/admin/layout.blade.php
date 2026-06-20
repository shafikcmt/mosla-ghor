<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — মসলা স্টোর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }
        #sidebar { transition: transform .2s ease; }
        /* Single (parent-less) link, e.g. Dashboard */
        .nav-link       { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#a7c8a7; transition:background .15s,color .15s; }
        .nav-link:hover { background:#14532d; color:#fff; }
        .nav-link.active{ background:#1a6b3a; color:#fff; font-weight:600; }
        /* Accordion parent (group toggle) */
        .nav-parent       { display:flex; align-items:center; gap:.6rem; width:100%; padding:.55rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#bcd9bc; transition:background .15s,color .15s; text-align:left; }
        .nav-parent:hover { background:#14532d; color:#fff; }
        .nav-parent.active{ color:#fff; font-weight:600; }
        .nav-parent .chevron { margin-left:auto; width:.85rem; height:.85rem; flex-shrink:0; transition:transform .2s ease; }
        .nav-parent.open .chevron { transform:rotate(90deg); }
        /* Accordion children */
        .nav-sub        { margin:.15rem 0 .35rem; padding-left:.45rem; border-left:1px solid #14532d; }
        .nav-child      { display:flex; align-items:center; gap:.5rem; padding:.4rem .75rem .4rem 1.75rem; border-radius:.4rem; font-size:.78125rem; color:#9bbf9b; transition:background .15s,color .15s; }
        .nav-child:hover{ background:#14532d; color:#fff; }
        .nav-child.active{ background:#1a6b3a; color:#fff; font-weight:600; }
        .nav-child .dot { width:.35rem; height:.35rem; border-radius:9999px; background:currentColor; flex-shrink:0; opacity:.55; }
        /* Slim scrollbar */
        #sidebar nav::-webkit-scrollbar { width:5px; }
        #sidebar nav::-webkit-scrollbar-thumb { background:#1a6b3a; border-radius:9999px; }
        #sidebar nav::-webkit-scrollbar-track { background:transparent; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@php
    $active = fn(string|array $p) => request()->routeIs($p);

    // ── Sidebar menu config ─────────────────────────────────────────────
    // Each group: label, icon (svg path d), items[ label, route, active(pattern) ].
    // Add 'show' => false to hide an item/group conditionally.
    $icons = [
        'box'      => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'tag'      => 'M7 7h.01M7 3h5a1.99 1.99 0 011.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z',
        'combo'    => 'M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10',
        'clip'     => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'users'    => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
        'truck'    => 'M8 17l4 4 4-4m0-5l-4-4-4 4M12 3v13',
        'receipt'  => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 11h.01M12 11h.01M15 11h.01M4 19h16a2 2 0 002-2V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2z',
        'key'      => 'M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z',
        'pin'      => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
        'stock'    => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
        'card'     => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'sliders'  => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4',
        'gear'     => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
        'globe'    => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9',
        'chat'     => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        'coin'     => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'shop'     => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
        'return'   => 'M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6',
        'faq'      => 'M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'star'     => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
        'review'   => 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        'wallet'   => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    ];

    $menuGroups = [
        [
            'label' => 'Catalog', 'icon' => $icons['box'],
            'items' => [
                ['label' => 'পণ্যসমূহ',  'route' => 'admin.products.index',   'active' => 'admin.products.*'],
                ['label' => 'ক্যাটাগরি', 'route' => 'admin.categories.index', 'active' => 'admin.categories.*'],
                ['label' => 'কম্বো',     'route' => 'admin.combos.index',     'active' => 'admin.combos.*'],
            ],
        ],
        [
            'label' => 'Sales', 'icon' => $icons['clip'],
            'items' => [
                ['label' => 'অর্ডার',   'route' => 'admin.orders.index',    'active' => 'admin.orders.*'],
                ['label' => 'কাস্টমার', 'route' => 'admin.customers.index', 'active' => 'admin.customers.*'],
            ],
        ],
        [
            'label' => 'Wholesale', 'icon' => $icons['chat'],
            'items' => [
                ['label' => 'Enquiry সমূহ',     'route' => 'admin.wholesale.enquiry.index', 'active' => 'admin.wholesale.enquiry.*'],
                ['label' => 'কোটেশন অনুমোদন',  'route' => 'admin.wholesale.quote.index',   'active' => 'admin.wholesale.quote.*'],
                ['label' => 'Chat Monitor',     'route' => 'admin.wholesale.chat.index',    'active' => 'admin.wholesale.chat.*'],
            ],
        ],
        [
            'label' => 'Vendor Management', 'icon' => $icons['shop'],
            'items' => [
                ['label' => 'ভেন্ডর / মার্চেন্ট', 'route' => 'admin.vendors.index',          'active' => 'admin.vendors.*'],
                ['label' => 'ভেন্ডর স্টক',        'route' => 'admin.vendor-stock.index',     'active' => 'admin.vendor-stock.*'],
                ['label' => 'ভেন্ডর কাস্টমার',    'route' => 'admin.vendor-customers.index', 'active' => 'admin.vendor-customers.*'],
                ['label' => 'পেআউট রিকুয়েস্ট',   'route' => 'admin.vendor-payouts.index',   'active' => 'admin.vendor-payouts.*'],
                ['label' => 'Vendor Wallet',       'route' => 'admin.vendor-wallet.index',    'active' => 'admin.vendor-wallet.*'],
                ['label' => 'Commission সেটিং',   'route' => 'admin.commission.settings.index', 'active' => 'admin.commission.*'],
            ],
        ],
        [
            'label' => 'Delivery', 'icon' => $icons['truck'],
            'items' => [
                ['label' => 'কুরিয়ার',        'route' => 'admin.couriers.index',             'active' => 'admin.couriers.*'],
                ['label' => 'ডেলিভারি রেট',    'route' => 'admin.delivery-rates.index',       'active' => 'admin.delivery-rates.*'],
                ['label' => 'API সেটিং',       'route' => 'admin.courier-api-settings.index', 'active' => 'admin.courier-api-settings.*'],
                ['label' => 'পার্সেল অর্ডার',  'route' => 'admin.courier-orders.index',        'active' => 'admin.courier-orders.*'],
                ['label' => 'ভেন্ডর পিকআপ',    'route' => 'admin.vendor-pickup-points.index', 'active' => 'admin.vendor-pickup-points.*'],
                ['label' => 'ডেলিভারি জোন',    'route' => 'admin.delivery-zones.index',        'active' => 'admin.delivery-zones.*'],
            ],
        ],
        [
            'label' => 'Customer Service', 'icon' => $icons['return'],
            'items' => [
                ['label' => 'রিটার্ন রিকোয়েস্ট', 'route' => 'admin.return-requests.index', 'active' => 'admin.return-requests.*'],
                ['label' => 'সাপোর্ট টিকেট',      'route' => 'admin.support-tickets.index', 'active' => 'admin.support-tickets.*'],
            ],
        ],
        [
            'label' => 'Content & Reviews', 'icon' => $icons['star'],
            'items' => [
                ['label' => 'FAQ',           'route' => 'admin.faqs.index',            'active' => 'admin.faqs.*'],
                ['label' => 'রিভিউ',         'route' => 'admin.reviews.index',         'active' => 'admin.reviews.*'],
                ['label' => 'পণ্য রিভিউ',     'route' => 'admin.product-reviews.index', 'active' => 'admin.product-reviews.*'],
                ['label' => 'ওয়েবসাইট সেটিং', 'route' => 'admin.website-settings.index', 'active' => 'admin.website-settings.*'],
            ],
        ],
        [
            'label' => 'Settings', 'icon' => $icons['gear'],
            'items' => [
                ['label' => 'পেমেন্ট সেটিং',  'route' => 'admin.payment-settings.index',  'active' => 'admin.payment-settings.*'],
                ['label' => 'ডেলিভারি সেটিং',  'route' => 'admin.delivery-settings.index', 'active' => 'admin.delivery-settings.*'],
                ['label' => 'জেনারেল সেটিং',   'route' => 'admin.general-settings.index',   'active' => 'admin.general-settings.*'],
                ['label' => 'লগইন সেটিং',      'route' => 'admin.auth-settings.index',      'active' => 'admin.auth-settings.*'],
            ],
        ],
    ];
@endphp

{{-- ── Mobile overlay ────────────────────────────────────────────── --}}
<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/50 z-20 hidden"
     onclick="closeSidebar()"></div>

{{-- ── Sidebar ────────────────────────────────────────────────────── --}}
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-30 w-60 bg-[#0d3520] flex flex-col
              -translate-x-full lg:translate-x-0">

    {{-- Brand (fixed top) --}}
    <div class="flex items-center gap-2.5 px-4 py-5 border-b border-[#14532d] flex-shrink-0">
        <div class="w-7 h-7 rounded-md bg-[#c9a227] flex items-center justify-center flex-shrink-0">
            <span class="text-[#0d3520] text-xs font-black">ম</span>
        </div>
        <div>
            <div class="text-white text-sm font-bold leading-tight">মসলা ঘর</div>
            <div class="text-[#4d7a5a] text-[10px] leading-none mt-0.5">Admin Panel</div>
        </div>
    </div>

    {{-- Nav (only this scrolls) --}}
    <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">

        {{-- Dashboard (single) --}}
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ $active('admin.dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            ড্যাশবোর্ড
        </a>

        {{-- Accordion groups --}}
        @foreach($menuGroups as $group)
            @php
                $items = array_filter($group['items'], fn($i) => ($i['show'] ?? true));
                $groupActive = collect($items)->contains(fn($i) => $active($i['active']));
            @endphp
            @if(count($items))
            <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }" class="pt-0.5">
                <button type="button" @click="open = !open"
                        class="nav-parent {{ $groupActive ? 'active' : '' }}"
                        :class="{ 'open': open }">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $group['icon'] }}"/>
                    </svg>
                    <span>{{ $group['label'] }}</span>
                    <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open" x-collapse x-cloak class="nav-sub">
                    @foreach($items as $item)
                        <a href="{{ route($item['route']) }}"
                           class="nav-child {{ $active($item['active']) ? 'active' : '' }}">
                            <span class="dot"></span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            @endif
        @endforeach

    </nav>

    {{-- Sidebar footer (fixed bottom) --}}
    <div class="px-4 py-3 border-t border-[#14532d] space-y-2.5 flex-shrink-0">

        {{-- Logged-in user --}}
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-full bg-[#1a6b3a] flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
            <div class="min-w-0">
                <div class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</div>
                <div class="text-[#4d7a5a] text-[10px] truncate">{{ auth()->user()->email }}</div>
            </div>
        </div>

        {{-- View website --}}
        <a href="{{ url('/') }}" target="_blank"
           class="flex items-center gap-2 text-[#4d7a5a] hover:text-[#c9a227] text-xs transition-colors">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            ওয়েবসাইট দেখুন
        </a>

        {{-- Logout --}}
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit"
                    class="flex items-center gap-2 text-[#4d7a5a] hover:text-red-400 text-xs transition-colors w-full">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                লগআউট
            </button>
        </form>

    </div>

</aside>

{{-- ── Page shell ─────────────────────────────────────────────────── --}}
<div class="lg:ml-60 flex flex-col min-h-screen">

    {{-- Top bar --}}
    <header class="sticky top-0 z-10 bg-white border-b border-gray-200 flex items-center justify-between px-4 py-3 gap-4">
        {{-- Mobile hamburger --}}
        <button onclick="openSidebar()"
                class="lg:hidden flex items-center justify-center w-8 h-8 rounded text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Page title --}}
        <h1 class="text-sm font-semibold text-gray-700 flex-1 truncate">
            @yield('title', 'Admin')
        </h1>

        {{-- Right side --}}
        <div class="flex items-center gap-3 flex-shrink-0">
            @include('partials.notification-bell', ['panel' => 'admin'])
            <span class="hidden sm:inline-block text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded">Admin</span>
            <a href="{{ url('/') }}" target="_blank"
               class="inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-gray-800 border border-gray-200 rounded px-2.5 py-1.5 hover:bg-gray-50 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                </svg>
                ওয়েবসাইট
            </a>
        </div>
    </header>

    {{-- Main content --}}
    <main class="flex-1 p-4 md:p-8">
        <div class="max-w-7xl mx-auto">

            @if(session('success'))
                <div class="mb-5 flex items-center gap-2 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-5 flex items-center gap-2 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-5 flex items-center gap-2 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg text-sm">
                    <svg class="w-4 h-4 flex-shrink-0 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                    <p class="font-medium mb-1">অনুগ্রহ করে নিচের ত্রুটিগুলো ঠিক করুন:</p>
                    <ul class="list-disc list-inside space-y-0.5 mt-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')

        </div>
    </main>

</div>

<script>
function openSidebar() {
    document.getElementById('sidebar').classList.remove('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeSidebar() {
    document.getElementById('sidebar').classList.add('-translate-x-full');
    document.getElementById('sidebar-overlay').classList.add('hidden');
    document.body.style.overflow = '';
}
</script>

</body>
</html>
