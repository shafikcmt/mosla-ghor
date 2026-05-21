<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'ভেন্ডর') — মসলা মার্ট</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
    <style>
        #sidebar { transition: transform .2s ease; }
        .nav-link       { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#a78bfa; transition:background .15s,color .15s; }
        .nav-link:hover { background:#3730a3; color:#fff; }
        .nav-link.active{ background:#4338ca; color:#fff; font-weight:600; }
        .nav-group-label{ font-size:.625rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#6d5fad; padding:.75rem .75rem .25rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@php
    $active = fn(string|array $p) => request()->routeIs($p);
    $vendor = $authVendor ?? null;
@endphp

<div id="sidebar-overlay" class="fixed inset-0 bg-black/50 z-20 hidden" onclick="closeSidebar()"></div>

<aside id="sidebar" class="fixed inset-y-0 left-0 z-30 w-60 bg-[#1e1b4b] flex flex-col overflow-y-auto -translate-x-full lg:translate-x-0">

    {{-- Brand --}}
    <div class="flex items-center gap-2.5 px-4 py-5 border-b border-[#3730a3]">
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
    <div class="mx-3 mt-3 px-3 py-2 rounded-lg
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

    <nav class="flex-1 px-2 py-3 space-y-0.5">

        <a href="{{ route('vendor.dashboard') }}"
           class="nav-link {{ $active('vendor.dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            ড্যাশবোর্ড
        </a>

        <p class="nav-group-label">পণ্য</p>

        <a href="{{ route('vendor.products.index') }}"
           class="nav-link {{ $active('vendor.products.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            আমার পণ্য
        </a>

        <a href="{{ route('vendor.products.create') }}"
           class="nav-link {{ $active('vendor.products.create') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            পণ্য যোগ করুন
        </a>

        <a href="{{ route('vendor.combos.index') }}"
           class="nav-link {{ $active('vendor.combos.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            আমার কম্বো
        </a>

        <p class="nav-group-label">অর্ডার</p>

        <a href="{{ route('vendor.orders.index') }}"
           class="nav-link {{ $active('vendor.orders.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            অর্ডারসমূহ
        </a>

        <p class="nav-group-label">আয়</p>

        <a href="{{ route('vendor.payouts.index') }}"
           class="nav-link {{ $active('vendor.payouts.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            পেআউট / উত্তোলন
        </a>

        <p class="nav-group-label">অ্যাকাউন্ট</p>

        <a href="{{ route('vendor.profile.index') }}"
           class="nav-link {{ $active('vendor.profile.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            শপ প্রোফাইল
        </a>

    </nav>

    <div class="px-4 py-3 border-t border-[#3730a3] space-y-2.5">
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
