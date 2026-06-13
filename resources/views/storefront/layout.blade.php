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

        /* Leave room for the app-like mobile bottom navigation (lg:hidden). */
        @media (max-width: 1023px) { body { padding-bottom: calc(60px + env(safe-area-inset-bottom)); } }
    </style>

    @yield('head')
</head>
<body class="min-h-screen flex flex-col">

{{-- Header (shared, responsive storefront navbar) --}}
@include('partials.storefront.navbar')

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
    <div class="max-w-6xl mx-auto px-4 py-6 text-center text-xs space-y-3">
        <div class="flex flex-wrap justify-center gap-4 text-green-300">
            <a href="/" class="hover:text-[#c9a227] transition-colors">হোম</a>
            <a href="/#products" class="hover:text-[#c9a227] transition-colors">পণ্য</a>
            <a href="{{ route('faq') }}" class="hover:text-[#c9a227] transition-colors">সাধারণ প্রশ্নোত্তর</a>
            <a href="/#contact" class="hover:text-[#c9a227] transition-colors">যোগাযোগ</a>
        </div>
        <div>© {{ date('Y') }} মসলা ঘর — খাঁটি মশলার আস্থার দোকান।</div>
    </div>
</footer>

{{-- Toast container --}}
<div id="ms-toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[200] hidden bg-[#14532d] text-white text-sm px-4 py-2.5 rounded-xl shadow-lg"></div>

{{-- Shared Mini-Cart drawer + enquiry-bag store (works for guests across pages).
     Float button suppressed — the mobile bottom nav already exposes the bag. --}}
@php($msHideFloat = true)
@include('partials.mini-cart')

{{-- App-like mobile/tablet bottom navigation --}}
@include('partials.bottom-nav')

@yield('scripts')
</body>
</html>
