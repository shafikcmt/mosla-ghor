{{--
    Mobile / tablet bottom navigation (app-like).
    ─────────────────────────────────────────────
    • Shown on mobile + tablet only (lg:hidden) — desktop keeps the top navbar.
    • Reusable across the whole customer funnel: included by home.blade.php and
      storefront/layout.blade.php (product detail, checkout, faq, enquiry bag).
    • z-40 so the contextual #combo-bar (z-50) and the cart drawer sit above it.
    • The "ব্যাগ" tab reuses the existing msCartOpen() from partials/mini-cart.
    • Cart badge uses [data-cart-badge] — auto-synced by the mini-cart store.

    Active-tab detection is path-based (no JS) so it works on every page.
--}}
@php
    $bnPath       = trim(request()->path(), '/');           // '' on home
    $bnIsCustomer = auth()->check() && auth()->user()->role === 'customer';

    $bnIsHome     = $bnPath === '';
    $bnIsOrders   = \Illuminate\Support\Str::startsWith($bnPath, ['account/orders', 'track-order']);
    $bnIsAccount  = \Illuminate\Support\Str::startsWith($bnPath, ['account', 'login', 'register'])
                    && ! $bnIsOrders;

    // Active = saffron; inactive = muted green-gray. Min 56px tall touch targets.
    $bnActive   = 'text-[#e2670a]';
    $bnInactive = 'text-gray-400';

    $ordersHref  = $bnIsCustomer ? route('customer.orders.index') : route('track-order');
    $accountHref = $bnIsCustomer
        ? route('customer.account')
        : route('customer.login') . '?redirect=' . urlencode(request()->getRequestUri());
@endphp

<nav class="lg:hidden fixed bottom-0 inset-x-0 z-40 bg-white border-t border-gray-200 shadow-[0_-2px_12px_rgba(0,0,0,0.06)]"
     style="padding-bottom: env(safe-area-inset-bottom);"
     aria-label="মোবাইল নেভিগেশন">
    <div class="grid grid-cols-5 max-w-lg mx-auto">

        {{-- Home --}}
        <a href="/" class="flex flex-col items-center justify-center gap-0.5 py-2 min-h-[56px] {{ $bnIsHome ? $bnActive : $bnInactive }} transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3v-6h6v6h3a1 1 0 001-1V10"/></svg>
            <span class="text-[10px] font-semibold leading-none">হোম</span>
        </a>

        {{-- Categories (jumps to the product grid / category chips) --}}
        <a href="/#products" class="flex flex-col items-center justify-center gap-0.5 py-2 min-h-[56px] {{ $bnInactive }} transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM13 5a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1V5zM4 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1v-5zM13 14a1 1 0 011-1h5a1 1 0 011 1v5a1 1 0 01-1 1h-5a1 1 0 01-1-1v-5z"/></svg>
            <span class="text-[10px] font-semibold leading-none">ক্যাটাগরি</span>
        </a>

        {{-- Combo / Bag — opens the shared cart + enquiry drawer --}}
        <button type="button" onclick="if(typeof msCartOpen==='function'){msCartOpen()}else{location.href='/#combo-builder'}"
                class="relative flex flex-col items-center justify-center gap-0.5 py-2 min-h-[56px] {{ $bnInactive }} transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
            <span data-cart-badge class="absolute top-1 right-[18%] bg-[#e2670a] text-white text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center" style="display:none;">0</span>
            <span class="text-[10px] font-semibold leading-none">ব্যাগ</span>
        </button>

        {{-- Orders --}}
        <a href="{{ $ordersHref }}" class="flex flex-col items-center justify-center gap-0.5 py-2 min-h-[56px] {{ $bnIsOrders ? $bnActive : $bnInactive }} transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
            <span class="text-[10px] font-semibold leading-none">অর্ডার</span>
        </a>

        {{-- Account --}}
        <a href="{{ $accountHref }}" class="flex flex-col items-center justify-center gap-0.5 py-2 min-h-[56px] {{ $bnIsAccount ? $bnActive : $bnInactive }} transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
            <span class="text-[10px] font-semibold leading-none">অ্যাকাউন্ট</span>
        </a>
    </div>
</nav>
