<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'পণ্য') — মসলা ঘর</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Serif+Bengali:wght@400;600;700&family=Noto+Sans+Bengali:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --green-deep : #0f3d22;
            --green-main : #14532d;
            --green-mid  : #166534;
            --gold       : #c9a227;
            --gold-light : #e2bb45;
            --cream      : #fef9ee;
        }
        body           { font-family: 'Noto Sans Bengali', sans-serif; background: var(--cream); color: #1c1917; }
        .font-serif-bn { font-family: 'Noto Serif Bengali', serif; }
        .gold-rule     { height: 1px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }
        .btn-gold {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 50%, var(--gold) 100%);
            background-size: 200% auto;
            transition: background-position .4s ease, box-shadow .2s;
        }
        .btn-gold:hover { background-position: right center; box-shadow: 0 8px 24px rgba(201,162,39,.45); }
    </style>

    @yield('head')
</head>
<body class="min-h-screen flex flex-col">

{{-- Header --}}
<header class="bg-[#0f3d22] sticky top-0 z-40 shadow-md">
    <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-2">
            <span class="font-serif-bn text-[#c9a227] text-xl font-bold">মসলা ঘর</span>
        </a>
        <nav class="flex items-center gap-3 sm:gap-4 text-sm">
            <a href="/" class="text-green-100 hover:text-white transition-colors hidden sm:inline">হোম</a>
            <a href="/#products" class="text-green-100 hover:text-white transition-colors hidden sm:inline">পণ্য</a>

            {{-- Retail cart (box builder lives on home) --}}
            <a href="/#combo-builder" class="relative text-green-100 hover:text-white transition-colors p-1" title="কার্ট" aria-label="কার্ট">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.3 4.6A1 1 0 005.6 19H19M9 22a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z"/></svg>
            </a>

            {{-- Paykari / wholesale enquiry bag --}}
            <a href="{{ route('wholesale.enquiry-bag') }}" class="relative text-green-100 hover:text-white transition-colors p-1" title="পাইকারি Enquiry Bag" aria-label="পাইকারি Enquiry Bag">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M6 2l1.5 3M18 2l-1.5 3M3 7h18l-1.4 12.1A2 2 0 0117.6 21H6.4a2 2 0 01-2-1.9L3 7zm6 4v4m6-4v4"/></svg>
                <span data-bag-badge class="absolute -top-1 -right-1 bg-[#c9a227] text-[#0f3d22] text-[10px] font-bold rounded-full min-w-[16px] h-4 px-1 flex items-center justify-center" style="display:none;">0</span>
            </a>

            @if(auth()->check() && auth()->user()->role === 'customer')
                <a href="{{ route('customer.account') }}"
                   class="bg-[#c9a227] text-[#0f3d22] font-semibold px-3 py-1.5 rounded-lg hover:brightness-110 transition">
                    <span class="hidden sm:inline">আমার অ্যাকাউন্ট</span><span class="sm:hidden">অ্যাকাউন্ট</span>
                </a>
            @else
                <a href="{{ route('customer.login') }}"
                   class="bg-[#c9a227] text-[#0f3d22] font-semibold px-3 py-1.5 rounded-lg hover:brightness-110 transition">
                    লগইন
                </a>
            @endif
        </nav>
    </div>
</header>

{{-- Main --}}
<main class="flex-1 w-full">
    <div class="max-w-6xl mx-auto px-4 py-6">

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
        @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="space-y-0.5">@foreach($errors->all() as $e)<li>• {{ $e }}</li>@endforeach</ul>
        </div>
        @endif

        @yield('content')
    </div>
</main>

{{-- Footer --}}
<footer class="bg-[#0f3d22] text-green-200 mt-10">
    <div class="gold-rule"></div>
    <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs">
        © {{ date('Y') }} মসলা ঘর — খাঁটি মশলার আস্থার দোকান।
    </div>
</footer>

{{-- Toast container --}}
<div id="ms-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[200] hidden bg-[#14532d] text-white text-sm px-4 py-2.5 rounded-xl shadow-lg"></div>

{{-- Shared Paykari Enquiry Bag (localStorage — works for guests across pages) --}}
<script>
    const MS_BAG_KEY = 'ms_enquiry_bag';
    function msBagGet() { try { return JSON.parse(localStorage.getItem(MS_BAG_KEY) || '[]'); } catch (e) { return []; } }
    function msBagSave(items) { try { localStorage.setItem(MS_BAG_KEY, JSON.stringify(items)); } catch (e) {} msBagBadge(); }
    function msBagCount() { return msBagGet().length; }
    function msBagBadge() {
        const n = msBagCount();
        document.querySelectorAll('[data-bag-badge]').forEach(function (el) {
            el.textContent = n; el.style.display = n > 0 ? 'flex' : 'none';
        });
    }
    function msBagAdd(id, slug, name, image, qty, unit) {
        const items = msBagGet();
        const ex = items.find(function (x) { return x.product_id === id; });
        if (ex) { ex.quantity = (parseFloat(ex.quantity) || 0) + (parseFloat(qty) || 1); }
        else { items.push({ product_id: id, slug: slug, name: name, image: image, quantity: parseFloat(qty) || 1, unit: unit || 'kg' }); }
        msBagSave(items);
        msToast('🛍️ Enquiry Bag-এ যোগ হয়েছে');
    }
    function msToast(msg) {
        const t = document.getElementById('ms-toast');
        if (!t) return;
        t.textContent = msg; t.classList.remove('hidden');
        clearTimeout(window.__msToastT);
        window.__msToastT = setTimeout(function () { t.classList.add('hidden'); }, 2500);
    }
    document.addEventListener('DOMContentLoaded', msBagBadge);
</script>

@yield('scripts')
</body>
</html>
