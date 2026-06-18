{{--
    MoslaMart customer storefront header — responsive, app-like.
    ──────────────────────────────────────────────────────────────
    Layout:  [ brand ]  [ desktop links ] ──flex spacer── [ search ] [ icons ]
    • Mobile  (<768px): brand left, icon group (search · cart · hamburger) right.
                        Hamburger opens a right slide-in drawer with overlay.
    • Desktop (≥768px): hamburger hidden, full menu + search + account visible.
    • Self-contained: computes $ws if the parent view didn't pass it, uses plain
      Tailwind classes (no custom CSS), and inlines its own null-safe drawer JS,
      so it works in BOTH home.blade.php and storefront/layout.blade.php.
    • Cart opens the shared mini-cart drawer (msCartOpen). Badge = [data-cart-badge].
--}}
@php
    $ws          = $ws ?? \App\Models\WebsiteSetting::allKeyed();
    $navCustomer = (Auth::check() && Auth::user()->role === 'customer') ? Auth::user() : null;

    // Active-state helpers (path + listing-mode based).
    $navPath      = trim(request()->path(), '/');             // '' on home
    $navMode      = strtolower((string) (request('mode') ?? request('tab') ?? ''));
    $navWholesale = in_array($navMode, ['wholesale', 'paykari'], true);
    $navIsHome    = $navPath === '';
    $navIsTrack   = \Illuminate\Support\Str::startsWith($navPath, 'track-order');

    $retailHref    = url('/') . '#products';
    $wholesaleHref = url('/') . '?mode=wholesale#products';
    $accountHref   = $navCustomer
        ? route('customer.account')
        : route('customer.login') . '?redirect=' . urlencode(request()->getRequestUri());

    // Desktop link styling (active = gold pill, idle = soft hover).
    $dLink   = 'px-3 py-2 rounded-lg text-sm font-medium text-green-100 hover:text-white hover:bg-white/10 transition-colors whitespace-nowrap';
    $dActive = 'px-3 py-2 rounded-lg text-sm font-semibold text-[#0f3d22] bg-[#c9a227] whitespace-nowrap';

    // Drawer link styling.
    $mLink   = 'flex items-center gap-3 px-5 py-3 text-[15px] text-gray-700 hover:bg-green-50 hover:text-[#14532d] transition-colors';
    $mActive = 'flex items-center gap-3 px-5 py-3 text-[15px] font-semibold text-[#14532d] bg-green-50 border-r-4 border-[#c9a227]';
@endphp

{{-- Admin-controlled top announcement marquee (shows on every storefront page). --}}
@include('partials.storefront.announcement', ['ws' => $ws])

<header class="bg-[#0f3d22]/95 backdrop-blur sticky top-0 z-50 shadow-md border-b border-green-900/60">
    <div class="max-w-7xl mx-auto px-3 sm:px-5 h-16 flex items-center gap-2">

        {{-- ── Brand (left) ── --}}
        <a href="/" class="group flex flex-col leading-none shrink-0">
            <span class="font-serif-bn text-[#c9a227] text-xl sm:text-2xl font-bold group-hover:text-[#e2bb45] transition-colors">মসলা ঘর</span>
            <span class="hidden sm:block text-green-400 text-[10px] tracking-[.2em] uppercase mt-0.5">Authentic Spice Store</span>
        </a>

        {{-- ── Desktop nav links ── --}}
        <nav class="hidden md:flex items-center gap-0.5 ml-3">
            <a href="/"                     class="{{ $navIsHome && ! $navWholesale && ! $navIsTrack ? $dActive : $dLink }}">হোম</a>
            <a href="{{ url('/') }}#products" class="{{ $dLink }}">ক্যাটাগরি</a>
            <a href="{{ $retailHref }}"       class="{{ $dLink }}">খুচরা পণ্য</a>
            <a href="{{ $wholesaleHref }}"    class="{{ $navWholesale ? $dActive : $dLink }}">পাইকারি</a>
            <button type="button" onclick="msCartOpen()" class="{{ $dLink }}">কম্বো/ব্যাগ</button>
            <a href="{{ route('track-order') }}" class="{{ $navIsTrack ? $dActive : $dLink }}">ট্র্যাক অর্ডার</a>
        </nav>

        {{-- ── Flexible spacer ── --}}
        <div class="flex-1"></div>

        {{-- ── Desktop search entry (jumps to the product grid) ── --}}
        <a href="{{ url('/') }}#products"
           class="hidden lg:flex items-center gap-2 w-56 xl:w-72 bg-white/10 hover:bg-white/15 border border-white/15 rounded-full px-4 py-2 text-green-200 text-sm transition-colors shrink-0">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.3-4.3M11 19a8 8 0 110-16 8 8 0 010 16z"/></svg>
            <span class="truncate">মসলা, চাল, ডাল খুঁজুন…</span>
        </a>

        {{-- ── Right icon group (cart · account · search · hamburger) ── --}}
        <div class="flex items-center gap-0.5 sm:gap-1 ml-1 lg:ml-2 shrink-0">

            {{-- Search icon (mobile + tablet, below the lg search box) --}}
            <a href="{{ url('/') }}#products"
               class="lg:hidden w-10 h-10 flex items-center justify-center rounded-full text-green-100 hover:text-white hover:bg-white/10 transition-colors"
               title="খুঁজুন" aria-label="খুঁজুন">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M21 21l-4.3-4.3M11 19a8 8 0 110-16 8 8 0 010 16z"/></svg>
            </a>

            {{-- Cart → shared drawer --}}
            <button type="button" onclick="msCartOpen()"
                    class="relative w-10 h-10 flex items-center justify-center rounded-full text-green-100 hover:text-white hover:bg-white/10 transition-colors"
                    title="কার্ট" aria-label="কার্ট">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
                <span data-cart-badge class="absolute top-0.5 right-0.5 bg-[#c9a227] text-[#0f3d22] text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center" style="display:none;">0</span>
            </button>

            {{-- Desktop account / login --}}
            <div class="hidden md:block ml-1">
                @if($navCustomer)
                <details class="relative">
                    <summary class="list-none cursor-pointer select-none flex items-center gap-1 bg-[#c9a227] text-[#0f3d22] text-sm font-semibold px-3 py-2 rounded-lg hover:brightness-110 transition">
                        আমার অ্যাকাউন্ট
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </summary>
                    <div class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-green-50 py-1.5 z-50 text-sm text-gray-700">
                        <a href="{{ route('customer.account') }}"      class="block px-4 py-2 hover:bg-green-50">আমার অ্যাকাউন্ট</a>
                        <a href="{{ route('customer.orders.index') }}" class="block px-4 py-2 hover:bg-green-50">অর্ডারসমূহ</a>
                        <form method="POST" action="{{ route('customer.logout') }}" class="border-t border-green-50 mt-1 pt-1">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-red-600 hover:bg-red-50">লগআউট</button>
                        </form>
                    </div>
                </details>
                @else
                <a href="{{ $accountHref }}"
                   class="bg-[#c9a227] text-[#0f3d22] text-sm font-semibold px-4 py-2 rounded-lg hover:brightness-110 transition whitespace-nowrap">
                    লগইন
                </a>
                @endif
            </div>

            {{-- Hamburger (mobile + tablet only) --}}
            <button type="button" data-drawer-open
                    class="md:hidden w-10 h-10 flex items-center justify-center rounded-full text-green-100 hover:text-white hover:bg-white/10 transition-colors"
                    aria-label="মেনু খুলুন">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
    </div>
</header>

{{-- ── Mobile / tablet slide-in drawer (right) ── --}}
<div data-drawer class="md:hidden fixed inset-0 z-[60] hidden" role="dialog" aria-modal="true" aria-label="মেনু">
    <div data-drawer-overlay class="absolute inset-0 bg-black/50 opacity-0 transition-opacity duration-300"></div>
    <aside data-drawer-panel
           class="absolute top-0 right-0 h-full w-72 max-w-[82%] bg-white shadow-2xl translate-x-full transition-transform duration-300 ease-out flex flex-col">

        <div class="flex items-center justify-between px-5 h-16 bg-[#0f3d22] shrink-0">
            <span class="font-serif-bn text-[#c9a227] text-xl font-bold">মসলা ঘর</span>
            <button type="button" data-drawer-close class="w-9 h-9 flex items-center justify-center rounded-full text-green-200 hover:text-white hover:bg-white/10" aria-label="বন্ধ করুন">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto py-2">
            <a href="/" class="{{ $navIsHome && ! $navWholesale && ! $navIsTrack ? $mActive : $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/></svg>
                হোম
            </a>
            <a href="{{ url('/') }}#products" class="{{ $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM13 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zM4 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1v-5zM13 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1v-5z"/></svg>
                ক্যাটাগরি
            </a>
            <a href="{{ $retailHref }}" class="{{ $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                খুচরা পণ্য
            </a>
            <a href="{{ $wholesaleHref }}" class="{{ $navWholesale ? $mActive : $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 7h18M3 7l1.5 12.5A1 1 0 005.5 21h13a1 1 0 001-.5L21 7M3 7l2-4h14l2 4M9 11v6m6-6v6"/></svg>
                পাইকারি পণ্য
            </a>
            <button type="button" onclick="msCartOpen()" class="{{ $mLink }} w-full text-left">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19"/></svg>
                কম্বো / ব্যাগ
            </button>
            <a href="{{ $navCustomer ? route('customer.orders.index') : route('track-order') }}" class="{{ $navIsTrack ? $mActive : $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                আমার অর্ডার
            </a>
            <a href="{{ $accountHref }}" class="{{ $mLink }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                {{ $navCustomer ? 'আমার অ্যাকাউন্ট' : 'অ্যাকাউন্ট / লগইন' }}
            </a>
        </nav>

        @if($navCustomer)
        <div class="p-4 border-t border-gray-100 shrink-0">
            <form method="POST" action="{{ route('customer.logout') }}">
                @csrf
                <button type="submit" class="w-full text-center text-sm font-semibold text-red-600 border border-red-200 rounded-lg py-2.5 hover:bg-red-50 transition-colors">
                    লগআউট
                </button>
            </form>
        </div>
        @else
        <div class="p-4 border-t border-gray-100 shrink-0">
            <a href="{{ $accountHref }}" class="block w-full text-center text-sm font-semibold text-[#0f3d22] bg-[#c9a227] rounded-lg py-2.5 hover:brightness-110 transition">
                লগইন / রেজিস্ট্রেশন
            </a>
        </div>
        @endif
    </aside>
</div>

<script>
(function () {
    var root = document.querySelector('[data-drawer]');
    if (!root || root.__msDrawerInit) return;
    root.__msDrawerInit = true;

    var panel   = root.querySelector('[data-drawer-panel]');
    var overlay = root.querySelector('[data-drawer-overlay]');
    var openBtns = document.querySelectorAll('[data-drawer-open]');

    function open() {
        root.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function () {
            panel.classList.remove('translate-x-full');
            overlay.classList.remove('opacity-0');
        });
    }
    function close() {
        panel.classList.add('translate-x-full');
        overlay.classList.add('opacity-0');
        document.body.style.overflow = '';
        setTimeout(function () { root.classList.add('hidden'); }, 300);
    }

    openBtns.forEach(function (b) { b.addEventListener('click', open); });
    root.querySelectorAll('[data-drawer-close], [data-drawer-overlay]').forEach(function (b) {
        b.addEventListener('click', close);
    });
    // Close after tapping any link or action inside the drawer.
    panel.querySelectorAll('a, button[type="submit"], [onclick]').forEach(function (el) {
        el.addEventListener('click', function () { setTimeout(close, 60); });
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !root.classList.contains('hidden')) close();
    });
})();
</script>
