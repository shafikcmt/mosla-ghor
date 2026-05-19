<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin') — মসলা স্টোর</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @stack('styles')
    <style>
        #sidebar { transition: transform .2s ease; }
        .nav-link       { display:flex; align-items:center; gap:.6rem; padding:.5rem .75rem; border-radius:.4rem; font-size:.8125rem; color:#a7c8a7; transition:background .15s,color .15s; }
        .nav-link:hover { background:#14532d; color:#fff; }
        .nav-link.active{ background:#1a6b3a; color:#fff; font-weight:600; }
        .nav-group-label{ font-size:.625rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#4d7a5a; padding:.75rem .75rem .25rem; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

@php
    $active = fn(string|array $p) => request()->routeIs($p);
@endphp

{{-- ── Mobile overlay ────────────────────────────────────────────── --}}
<div id="sidebar-overlay"
     class="fixed inset-0 bg-black/50 z-20 hidden"
     onclick="closeSidebar()"></div>

{{-- ── Sidebar ────────────────────────────────────────────────────── --}}
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-30 w-60 bg-[#0d3520] flex flex-col overflow-y-auto
              -translate-x-full lg:translate-x-0">

    {{-- Brand --}}
    <div class="flex items-center gap-2.5 px-4 py-5 border-b border-[#14532d]">
        <div class="w-7 h-7 rounded-md bg-[#c9a227] flex items-center justify-center flex-shrink-0">
            <span class="text-[#0d3520] text-xs font-black">ম</span>
        </div>
        <div>
            <div class="text-white text-sm font-bold leading-tight">মসলা ঘর</div>
            <div class="text-[#4d7a5a] text-[10px] leading-none mt-0.5">Admin Panel</div>
        </div>
    </div>

    {{-- Nav --}}
    <nav class="flex-1 px-2 py-3 space-y-0.5">

        {{-- Dashboard --}}
        <a href="{{ route('admin.dashboard') }}"
           class="nav-link {{ $active('admin.dashboard') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            ড্যাশবোর্ড
        </a>

        {{-- Catalog --}}
        <p class="nav-group-label">Catalog</p>

        <a href="{{ route('admin.products.index') }}"
           class="nav-link {{ $active('admin.products.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            পণ্যসমূহ
        </a>

        <a href="{{ route('admin.combos.index') }}"
           class="nav-link {{ $active('admin.combos.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            কম্বো
        </a>

        {{-- Sales --}}
        <p class="nav-group-label">Sales</p>

        <a href="{{ route('admin.orders.index') }}"
           class="nav-link {{ $active('admin.orders.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            অর্ডার
        </a>

        {{-- Settings --}}
        <p class="nav-group-label">Settings</p>

        <a href="{{ route('admin.payment-settings.index') }}"
           class="nav-link {{ $active('admin.payment-settings.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
            </svg>
            পেমেন্ট সেটিং
        </a>

        <a href="{{ route('admin.delivery-settings.index') }}"
           class="nav-link {{ $active('admin.delivery-settings.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            ডেলিভারি সেটিং
        </a>

        <a href="{{ route('admin.delivery-zones.index') }}"
           class="nav-link {{ $active('admin.delivery-zones.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            ডেলিভারি জোন
        </a>

        <a href="{{ route('admin.general-settings.index') }}"
           class="nav-link {{ $active('admin.general-settings.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
            </svg>
            জেনারেল সেটিং
        </a>

        {{-- Content --}}
        <p class="nav-group-label">Content</p>

        <a href="{{ route('admin.faqs.index') }}"
           class="nav-link {{ $active('admin.faqs.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            FAQ
        </a>

        <a href="{{ route('admin.reviews.index') }}"
           class="nav-link {{ $active('admin.reviews.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
            রিভিউ
        </a>

        <a href="{{ route('admin.website-settings.index') }}"
           class="nav-link {{ $active('admin.website-settings.*') ? 'active' : '' }}">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
            </svg>
            ওয়েবসাইট সেটিং
        </a>

    </nav>

    {{-- Sidebar footer --}}
    <div class="px-4 py-3 border-t border-[#14532d] space-y-2.5">

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
