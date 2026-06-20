<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ভেন্ডর') — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @stack('styles')
    <style>
        [x-cloak] { display: none !important; }
        #sidebar { transition: transform .2s ease; }
        .nav-link       { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#a78bfa; transition:background .15s,color .15s; }
        .nav-link:hover { background:#3730a3; color:#fff; }
        .nav-link.active{ background:#4338ca; color:#fff; font-weight:600; }
        .nav-parent       { display:flex; align-items:center; gap:.6rem; width:100%; padding:.55rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#b9a8f5; transition:background .15s,color .15s; text-align:left; }
        .nav-parent:hover { background:#3730a3; color:#fff; }
        .nav-parent.active{ color:#fff; font-weight:600; }
        .nav-parent .chevron { margin-left:auto; width:.85rem; height:.85rem; flex-shrink:0; transition:transform .2s ease; }
        .nav-parent.open .chevron { transform:rotate(90deg); }
        .nav-sub        { margin:.15rem 0 .35rem; padding-left:.45rem; border-left:1px solid #3730a3; }
        .nav-child      { display:flex; align-items:center; gap:.5rem; padding:.4rem .75rem .4rem 1.75rem; border-radius:.4rem; font-size:.78125rem; color:#a78bfa; transition:background .15s,color .15s; }
        .nav-child:hover{ background:#3730a3; color:#fff; }
        .nav-child.active{ background:#4338ca; color:#fff; font-weight:600; }
        .nav-child .dot { width:.35rem; height:.35rem; border-radius:9999px; background:currentColor; flex-shrink:0; opacity:.55; }
        #sidebar nav::-webkit-scrollbar { width:5px; }
        #sidebar nav::-webkit-scrollbar-thumb { background:#4338ca; border-radius:9999px; }
        #sidebar nav::-webkit-scrollbar-track { background:transparent; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@php
    $active = fn($p) => request()->routeIs(...(array) $p);
    $vendor = $authVendor ?? null;

    $canStock    = \App\Support\VendorSettings::vendorCanManageStock();
    $canOrder    = \App\Support\VendorSettings::vendorCanCreateOrder();
    $canCustomer = \App\Support\VendorSettings::vendorCanCreateCustomer();
    $canPickup   = \App\Models\CourierSetting::current()->vendorCanSetupPickup();

    $icons = [
        'box'    => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
        'cart'   => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
        'clip'   => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2',
        'chat'   => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z',
        'wallet' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
        'user'   => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    ];

    $menuGroups = [
        [
            'label' => 'পণ্য', 'icon' => $icons['box'],
            'items' => [
                ['label' => 'আমার পণ্য',        'route' => 'vendor.products.index',  'active' => 'vendor.products.*'],
                ['label' => 'পণ্য যোগ করুন',     'route' => 'vendor.products.create', 'active' => 'vendor.products.create'],
                ['label' => 'আমার কম্বো',        'route' => 'vendor.combos.index',    'active' => 'vendor.combos.*'],
                ['label' => 'স্টক ম্যানেজমেন্ট', 'route' => 'vendor.stock.index',     'active' => 'vendor.stock.*', 'show' => $canStock],
            ],
        ],
        [
            'label' => 'বিক্রয় ও কাস্টমার', 'icon' => $icons['cart'],
            'items' => [
                ['label' => 'নতুন বিক্রয় (POS)', 'route' => 'vendor.pos.create',    'active' => 'vendor.pos.create', 'show' => $canOrder],
                ['label' => 'বিক্রয় তালিকা',     'route' => 'vendor.pos.index',     'active' => ['vendor.pos.index', 'vendor.pos.show'], 'show' => $canOrder],
                ['label' => 'কাস্টমার',          'route' => 'vendor.customers.index', 'active' => 'vendor.customers.*', 'show' => $canCustomer],
            ],
        ],
        [
            'label' => 'অর্ডার', 'icon' => $icons['clip'],
            'items' => [
                ['label' => 'অর্ডারসমূহ',     'route' => 'vendor.orders.index',        'active' => 'vendor.orders.*'],
                ['label' => 'পিকআপ পয়েন্ট',  'route' => 'vendor.pickup-points.index', 'active' => 'vendor.pickup-points.*', 'show' => $canPickup],
            ],
        ],
        [
            'label' => 'পাইকারি', 'icon' => $icons['chat'],
            'items' => [
                ['label' => 'নতুন Enquiry', 'route' => 'vendor.wholesale.enquiry.index', 'active' => 'vendor.wholesale.enquiry.*'],
                ['label' => 'কোটেশন',       'route' => 'vendor.wholesale.quote.index',   'active' => 'vendor.wholesale.quote.*'],
            ],
        ],
        [
            'label' => 'আয়', 'icon' => $icons['wallet'],
            'items' => [
                ['label' => 'আয় / কমিশন',    'route' => 'vendor.wholesale.earnings.index', 'active' => 'vendor.wholesale.earnings.*'],
                ['label' => 'পেআউট / উত্তোলন', 'route' => 'vendor.payouts.index',            'active' => 'vendor.payouts.*'],
            ],
        ],
        [
            'label' => 'অ্যাকাউন্ট', 'icon' => $icons['user'],
            'items' => [
                ['label' => 'শপ প্রোফাইল', 'route' => 'vendor.profile.index', 'active' => 'vendor.profile.*'],
            ],
        ],
    ];
@endphp

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden" onclick="closeSidebar()"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-60 bg-[#1e1b4b] flex flex-col -translate-x-full lg:translate-x-0">

    {{-- Brand (fixed top) --}}
    <div class="flex items-center gap-2.5 px-4 py-5 border-b border-[#3730a3] flex-shrink-0">
        <div class="w-7 h-7 rounded-md bg-[#c9a227] flex items-center justify-center flex-shrink-0">
            <span class="text-[#1e1b4b] text-xs font-black">ভ</span>
        </div>
        <div>
            <div class="text-white text-sm font-bold leading-tight">ভেন্ডর প্যানেল</div>
            <div class="text-[#6d5fad] text-[10px] leading-none mt-0.5">মসলা মার্ট</div>
        </div>
    </div>

    {{-- Vendor status badge --}}
    @if($vendor)
    <div class="mx-3 mt-3 px-3 py-2 rounded-lg flex-shrink-0
        @if($vendor->status === 'approved') bg-green-900/40 border border-green-700
        @elseif($vendor->status === 'pending') bg-yellow-900/40 border border-yellow-600
        @elseif($vendor->status === 'suspended') bg-red-900/40 border border-red-700
        @else bg-gray-800 border border-gray-600 @endif">
        <div class="text-white text-xs font-semibold truncate">{{ $vendor->shop_name }}</div>
        <div class="text-[10px] mt-0.5
            @if($vendor->status === 'approved') text-green-400
            @elseif($vendor->status === 'pending') text-yellow-400
            @elseif($vendor->status === 'suspended') text-red-400
            @else text-gray-400 @endif">
            @if($vendor->status === 'approved') ✓ অনুমোদিত
            @elseif($vendor->status === 'pending') ⏳ অনুমোদন অপেক্ষায়
            @elseif($vendor->status === 'suspended') ✗ স্থগিত
            @else ✗ প্রত্যাখ্যাত @endif
        </div>
    </div>
    @endif

    {{-- Nav (only this scrolls) --}}
    <nav class="flex-1 px-2 py-3 space-y-0.5 overflow-y-auto">

        {{-- Dashboard (single) --}}
        <a href="{{ route('vendor.dashboard') }}"
           class="nav-link {{ $active('vendor.dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
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

    {{-- Footer (fixed bottom) --}}
    <div class="px-4 py-3 border-t border-[#3730a3] space-y-2.5 flex-shrink-0">
        <div class="flex items-center gap-2.5">
            <div class="w-7 h-7 rounded-full bg-[#4338ca] flex items-center justify-center flex-shrink-0">
                <span class="text-white text-xs font-bold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            </div>
            <div class="min-w-0">
                <div class="text-white text-xs font-medium truncate">{{ auth()->user()->name }}</div>
                <div class="text-[#6d5fad] text-[10px] truncate">{{ auth()->user()->email }}</div>
            </div>
        </div>

        <a href="{{ url('/') }}" target="_blank"
           class="flex items-center gap-2 text-[#6d5fad] hover:text-[#c9a227] text-xs transition-colors">
            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
            </svg>
            ওয়েবসাইট দেখুন
        </a>

        <form method="POST" action="{{ route('vendor.logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-2 text-[#6d5fad] hover:text-red-400 text-xs transition-colors w-full">
                <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                লগআউট
            </button>
        </form>
    </div>

</aside>

<div class="lg:ml-60 flex flex-col min-h-screen">

    <header class="sticky top-0 z-10 bg-white border-b border-gray-200 flex items-center justify-between px-4 py-3 gap-4">
        <button onclick="openSidebar()" class="lg:hidden flex items-center justify-center w-8 h-8 rounded text-gray-500 hover:bg-gray-100 transition-colors flex-shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <h1 class="text-sm font-semibold text-gray-700 flex-1 truncate">@yield('title', 'ভেন্ডর প্যানেল')</h1>
        <div class="flex items-center gap-3 flex-shrink-0">
            @include('partials.notification-bell', ['panel' => 'vendor'])
            <span class="hidden sm:inline-block text-xs text-indigo-600 bg-indigo-50 px-2 py-1 rounded font-medium">ভেন্ডর</span>
        </div>
    </header>

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
@stack('scripts')
</body>
</html>
