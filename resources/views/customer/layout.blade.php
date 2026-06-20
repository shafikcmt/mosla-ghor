<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'আমার অ্যাকাউন্ট') — মসলা ঘর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Noto Sans Bengali', sans-serif; background: #fef9ee; }
        [x-cloak] { display: none !important; }
        .c-link        { display:flex; align-items:center; gap:.65rem; padding:.65rem 1rem; font-size:.875rem; border-radius:.5rem; transition:background .15s,color .15s; }
        .c-link.active { background:#14532d; color:#fff; font-weight:600; }
        .c-link:not(.active){ color:#374151; }
        .c-link:not(.active):hover { background:#ecfdf5; }
        .c-parent      { display:flex; align-items:center; gap:.65rem; width:100%; padding:.65rem 1rem; font-size:.875rem; border-radius:.5rem; color:#374151; transition:background .15s; text-align:left; }
        .c-parent:hover{ background:#f3f4f6; }
        .c-parent.active{ color:#14532d; font-weight:600; }
        .c-parent .chevron { margin-left:auto; width:.9rem; height:.9rem; flex-shrink:0; transition:transform .2s ease; }
        .c-parent.open .chevron { transform:rotate(90deg); }
        .c-sub         { margin:.1rem 0 .25rem 1.55rem; padding-left:.5rem; border-left:1px solid #e5e7eb; }
        .c-child       { display:flex; align-items:center; gap:.55rem; padding:.5rem .75rem; font-size:.8125rem; border-radius:.5rem; transition:background .15s,color .15s; }
        .c-child.active{ background:#14532d; color:#fff; font-weight:600; }
        .c-child:not(.active){ color:#4b5563; }
        .c-child:not(.active):hover { background:#ecfdf5; }
    </style>
</head>
<body class="min-h-screen">

@php
    $active = fn($p) => request()->routeIs(...(array) $p);

    $singles = [
        ['route' => 'customer.account',            'active' => 'customer.account',            'label' => 'ড্যাশবোর্ড',   'icon' => '🏠'],
        ['route' => 'customer.notifications.index', 'active' => 'customer.notifications.*',     'label' => 'নোটিফিকেশন',    'icon' => '🔔'],
    ];

    $menuGroups = [
        [
            'label' => 'শপিং', 'icon' => '🛍️',
            'items' => [
                ['route' => 'customer.orders.index',   'active' => 'customer.orders.*',   'label' => 'আমার অর্ডার',     'icon' => '📦'],
                ['route' => 'customer.returns.index',  'active' => 'customer.returns.*',  'label' => 'রিটার্ন/রিফান্ড', 'icon' => '↩️'],
                ['route' => 'customer.wishlist.index', 'active' => 'customer.wishlist.*', 'label' => 'উইশলিস্ট',        'icon' => '❤️'],
            ],
        ],
        [
            'label' => 'পাইকারি', 'icon' => '🏭',
            'items' => [
                ['route' => 'customer.wholesale.enquiry.index', 'active' => 'customer.wholesale.enquiry.*', 'label' => 'পাইকারি Enquiry', 'icon' => '🏭'],
                ['route' => 'customer.wholesale.quote.index',   'active' => 'customer.wholesale.quote.*',   'label' => 'কোটেশন',          'icon' => '📋'],
            ],
        ],
        [
            'label' => 'অ্যাকাউন্ট', 'icon' => '👤',
            'items' => [
                ['route' => 'customer.profile.edit',    'active' => 'customer.profile.*',   'label' => 'প্রোফাইল', 'icon' => '👤'],
                ['route' => 'customer.addresses.index', 'active' => 'customer.addresses.*', 'label' => 'ঠিকানা',   'icon' => '📍'],
                ['route' => 'customer.support.index',   'active' => 'customer.support.*',   'label' => 'সাপোর্ট',  'icon' => '💬'],
            ],
        ],
    ];
@endphp

{{-- Top Header --}}
<header class="bg-[#14532d] shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <button id="sidebar-toggle" class="md:hidden text-green-300 hover:text-white p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <a href="/" class="text-[#c9a227] text-lg font-bold">মসলা ঘর</a>
            <span class="text-green-400 text-xs hidden sm:inline">/ অ্যাকাউন্ট</span>
        </div>
        <div class="flex items-center gap-3">
            @php $unreadNotif = Auth::user()->unreadNotifications()->count(); @endphp
            <a href="{{ route('customer.notifications.index') }}"
               class="relative flex items-center justify-center w-9 h-9 rounded-lg text-green-200 hover:bg-green-900/40 transition-colors"
               title="নোটিফিকেশন">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                @if($unreadNotif > 0)
                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-red-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $unreadNotif > 9 ? '9+' : $unreadNotif }}</span>
                @endif
            </a>
            <span class="text-green-200 text-sm hidden sm:inline">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('customer.logout') }}" class="inline">
                @csrf
                <button type="submit" class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-lg transition-colors">
                    লগআউট
                </button>
            </form>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 py-5 flex gap-5">

    {{-- Sidebar --}}
    <aside id="sidebar"
           class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r border-gray-200 overflow-y-auto pt-20
                  -translate-x-full md:translate-x-0 md:static md:inset-auto md:w-56 md:pt-0 md:bg-transparent md:border-none
                  transition-transform duration-200 shrink-0">
        <nav class="bg-white rounded-xl border border-gray-100 shadow-sm p-2 space-y-0.5">

            {{-- Single items --}}
            @foreach($singles as $item)
            <a href="{{ route($item['route']) }}"
               class="c-link {{ $active($item['active']) ? 'active' : '' }}">
                <span class="text-base">{{ $item['icon'] }}</span>
                <span>{{ $item['label'] }}</span>
            </a>
            @endforeach

            {{-- Accordion groups --}}
            @foreach($menuGroups as $group)
                @php $groupActive = collect($group['items'])->contains(fn($i) => $active($i['active'])); @endphp
                <div x-data="{ open: {{ $groupActive ? 'true' : 'false' }} }">
                    <button type="button" @click="open = !open"
                            class="c-parent {{ $groupActive ? 'active' : '' }}"
                            :class="{ 'open': open }">
                        <span class="text-base">{{ $group['icon'] }}</span>
                        <span>{{ $group['label'] }}</span>
                        <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>
                    <div x-show="open" x-collapse x-cloak class="c-sub">
                        @foreach($group['items'] as $item)
                        <a href="{{ route($item['route']) }}"
                           class="c-child {{ $active($item['active']) ? 'active' : '' }}">
                            <span class="text-sm">{{ $item['icon'] }}</span>
                            <span>{{ $item['label'] }}</span>
                        </a>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Logout --}}
            <form method="POST" action="{{ route('customer.logout') }}" class="pt-1 border-t border-gray-100 mt-1">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                    <span class="text-base">🚪</span><span>লগআউট</span>
                </button>
            </form>
        </nav>
    </aside>

    {{-- Overlay for mobile --}}
    <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 z-20 hidden md:hidden" onclick="closeSidebar()"></div>

    {{-- Main Content --}}
    <main class="flex-1 min-w-0">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg text-sm">
            {{ session('success') }}
        </div>
        @endif
        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            {{ session('error') }}
        </div>
        @endif
        @if(session('info'))
        <div class="mb-4 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-sm">
            {{ session('info') }}
        </div>
        @endif
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        @yield('content')
    </main>
</div>

<script>
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    document.getElementById('sidebar-toggle')?.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    });
    function closeSidebar() {
        sidebar.classList.add('-translate-x-full');
        overlay.classList.add('hidden');
    }
</script>
@yield('scripts')
</body>
</html>
