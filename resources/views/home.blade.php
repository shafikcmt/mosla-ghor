@php
/* Prepare a clean JS-safe product payload — no extra queries, activePrices already eager-loaded */
$productsForJs = $products->map(function ($p) {
    return [
        'id'                => $p->id,
        'name_bn'           => $p->name_bn,
        'name_en'           => $p->name_en,
        'short_description' => $p->short_description,
        'description'       => $p->description,
        'main_image'        => $p->main_image ? asset($p->main_image) : null,
        'video_url'         => $p->video_url,
        'stock'             => (int) $p->stock,
        'prices'            => $p->activePrices->map(function ($pr) {
            return [
                'id'                 => $pr->id,
                'label'              => $pr->label,
                'quantity_gram'      => (int) $pr->quantity_gram,
                'final_price'        => (float) $pr->final_price,
                'is_manual_override' => (bool) $pr->is_manual_override,
            ];
        })->values()->all(),
    ];
})->keyBy('id');
@endphp
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>মসলা ঘর — খাঁটি মশলার আস্থার দোকান</title>
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
        .text-gold     { color: var(--gold); }
        .text-cream    { color: var(--cream); }

        /* Hero radial glow */
        .hero-bg {
            background-color: var(--green-main);
            background-image:
                radial-gradient(ellipse at 15% 50%, rgba(201,162,39,.10) 0%, transparent 55%),
                radial-gradient(ellipse at 85% 20%, rgba(201,162,39,.07) 0%, transparent 45%);
        }

        /* Thin gold divider */
        .gold-rule { height: 1px; background: linear-gradient(90deg, transparent, var(--gold), transparent); }

        /* CTA button shimmer */
        .btn-gold {
            background: linear-gradient(135deg, var(--gold) 0%, var(--gold-light) 50%, var(--gold) 100%);
            background-size: 200% auto;
            transition: background-position .4s ease, box-shadow .2s;
        }
        .btn-gold:hover { background-position: right center; box-shadow: 0 8px 24px rgba(201,162,39,.45); }

        /* Nav backdrop */
        .nav-blur { backdrop-filter: blur(8px); background-color: rgba(15,61,34,.96); }

        /* Announcement marquee */
        @keyframes marquee { 0% { transform:translateX(100%) } 100% { transform:translateX(-100%) } }
        .marquee-text { animation: marquee 30s linear infinite; white-space: nowrap; }

        /* Product card hover */
        .product-card { transition: transform .22s ease, box-shadow .22s ease; }
        .product-card:hover { transform: translateY(-5px); box-shadow: 0 20px 40px rgba(20,83,45,.18); }

        /* Pack price chip */
        .p-chip {
            background: #f0faf4;
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            transition: background .15s, border-color .15s;
            cursor: pointer;
            user-select: none;
        }
        .p-chip:hover { background: #dcfce7; border-color: #166534; }
        .p-chip.active {
            background: var(--green-main);
            border-color: var(--green-main);
        }
        .p-chip.active .chip-lbl { color: #86efac; }
        .p-chip.active .chip-val { color: #fff; }

        /* Modal */
        #modal-overlay  { display:none; }
        #modal-wrapper  { display:none; }
        @keyframes modalIn { from { opacity:0; transform:scale(.96) translateY(12px); } to { opacity:1; transform:scale(1) translateY(0); } }
        .modal-enter { animation: modalIn .22s ease forwards; }

        /* List view */
        #list-view { display:none; flex-direction:column; gap:.75rem; }

        /* Scrollbar inside modal */
        #modal-body::-webkit-scrollbar { width: 4px; }
        #modal-body::-webkit-scrollbar-thumb { background:#d1fae5; border-radius:2px; }

        /* Scrollbar inside combo summary */
        #combo-items-wrap::-webkit-scrollbar { width: 3px; }
        #combo-items-wrap::-webkit-scrollbar-thumb { background:#2d6a4f; border-radius:2px; }

        /* Picker row highlight on goToCombo */
        .picker-highlight { background:#dcfce7 !important; }
    </style>
</head>
<body class="min-h-screen">

{{-- ━━━━━━━━━━━━━━━━  ANNOUNCEMENT BAR  ━━━━━━━━━━━━━━━━ --}}
<div class="bg-[#c9a227] text-[#0f3d22] py-2 overflow-hidden text-sm font-semibold">
    <p class="marquee-text">
        &nbsp;&nbsp;&nbsp;✦ ঈদ স্পেশাল — এখনই অর্ডার করুন এবং পান বিশেষ ছাড়!
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✦ ১০০% খাঁটি মশলা — কোনো ভেজাল নেই
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✦ সারা বাংলাদেশে হোম ডেলিভারি
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✦ ঈদ স্পেশাল — এখনই অর্ডার করুন এবং পান বিশেষ ছাড়!
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✦ ১০০% খাঁটি মশলা — কোনো ভেজাল নেই
        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;✦ সারা বাংলাদেশে হোম ডেলিভারি &nbsp;&nbsp;&nbsp;
    </p>
</div>

{{-- ━━━━━━━━━━━━━━━━  NAVBAR  ━━━━━━━━━━━━━━━━ --}}
<nav class="nav-blur sticky top-0 z-50 border-b border-green-900 shadow-lg">
    <div class="max-w-7xl mx-auto px-5 py-4 flex justify-between items-center">
        <a href="/" class="group flex flex-col leading-none">
            <span class="text-[#c9a227] text-2xl font-bold font-serif-bn group-hover:text-[#e2bb45] transition-colors">মসলা ঘর</span>
            <span class="text-green-400 text-[10px] tracking-[.2em] uppercase mt-0.5">Authentic Spice Store</span>
        </a>

        <div class="hidden md:flex items-center gap-7">
            <a href="#products" class="text-green-200 hover:text-[#c9a227] text-sm transition-colors">পণ্যসমূহ</a>
            <a href="#why-us"   class="text-green-200 hover:text-[#c9a227] text-sm transition-colors">আমাদের সম্পর্কে</a>
            <a href="#contact"  class="text-green-200 hover:text-[#c9a227] text-sm transition-colors">যোগাযোগ</a>
            <a href="/admin/products" class="border border-green-600 text-green-300 hover:border-[#c9a227] hover:text-[#c9a227] text-xs px-3 py-1.5 rounded-full transition-colors">Admin</a>
        </div>

        <button id="nav-toggle" class="md:hidden text-green-300 hover:text-[#c9a227] p-1" aria-label="menu">
            <svg id="ico-open"  class="w-6 h-6"        fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg id="ico-close" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <div id="mobile-menu" class="hidden md:hidden border-t border-green-900 px-5 py-4 flex-col gap-4">
        <a href="#products" class="text-green-200 hover:text-[#c9a227] text-sm">পণ্যসমূহ</a>
        <a href="#why-us"   class="text-green-200 hover:text-[#c9a227] text-sm">আমাদের সম্পর্কে</a>
        <a href="#contact"  class="text-green-200 hover:text-[#c9a227] text-sm">যোগাযোগ</a>
        <a href="/admin/products" class="text-green-400 hover:text-[#c9a227] text-xs">Admin Panel →</a>
    </div>
</nav>

{{-- ━━━━━━━━━━━━━━━━  HERO  ━━━━━━━━━━━━━━━━ --}}
<section class="hero-bg py-20 md:py-28 px-5">
    <div class="max-w-4xl mx-auto text-center">

        <div class="flex items-center justify-center gap-4 mb-8">
            <div class="h-px w-20 bg-gradient-to-r from-transparent to-[#c9a227] opacity-70"></div>
            <span class="text-[#c9a227] text-xs tracking-[.35em] uppercase font-semibold">ঈদ স্পেশাল কালেকশন</span>
            <div class="h-px w-20 bg-gradient-to-l from-transparent to-[#c9a227] opacity-70"></div>
        </div>

        <h1 class="font-serif-bn text-cream leading-tight mb-6">
            <span class="block text-5xl md:text-7xl font-bold">খাঁটি মশলার</span>
            <span class="block text-4xl md:text-6xl font-bold text-[#c9a227] mt-1">অপূর্ব স্বাদ</span>
        </h1>
        <p class="text-green-200 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed mb-10">
            প্রকৃতির সেরা উপাদান থেকে তৈরি, ভেজালমুক্ত খাঁটি মশলা —
            আপনার রান্নাকে করে তুলুন অতুলনীয় ও সুস্বাদু।
        </p>
        <a href="#products" class="btn-gold inline-flex items-center gap-2 text-[#0f3d22] font-bold text-base px-10 py-3.5 rounded-full shadow-xl">
            পণ্য দেখুন
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
        </a>

        <div class="mt-16 flex flex-wrap justify-center gap-8 md:gap-12">
            <div class="text-center">
                <div class="text-[#c9a227] text-4xl font-bold font-serif-bn">{{ $products->count() }}+</div>
                <div class="text-green-400 text-xs mt-1 uppercase tracking-wider">খাঁটি মশলা</div>
            </div>
            <div class="w-px bg-green-700 self-stretch hidden md:block"></div>
            <div class="text-center">
                <div class="text-[#c9a227] text-4xl font-bold font-serif-bn">১০০%</div>
                <div class="text-green-400 text-xs mt-1 uppercase tracking-wider">প্রাকৃতিক</div>
            </div>
            <div class="w-px bg-green-700 self-stretch hidden md:block"></div>
            <div class="text-center">
                <div class="text-[#c9a227] text-4xl font-bold font-serif-bn">দ্রুত</div>
                <div class="text-green-400 text-xs mt-1 uppercase tracking-wider">ডেলিভারি</div>
            </div>
            <div class="w-px bg-green-700 self-stretch hidden md:block"></div>
            <div class="text-center">
                <div class="text-[#c9a227] text-4xl font-bold font-serif-bn">সেরা</div>
                <div class="text-green-400 text-xs mt-1 uppercase tracking-wider">মানের নিশ্চয়তা</div>
            </div>
        </div>
    </div>
</section>

<div class="gold-rule"></div>

{{-- ━━━━━━━━━━━━━━━━  PRODUCTS  ━━━━━━━━━━━━━━━━ --}}
<section id="products" class="py-16 md:py-20 px-5">
    <div class="max-w-7xl mx-auto">

        {{-- Section header + view toggle --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-5 mb-10">
            <div>
                <div class="flex items-center gap-4 mb-2">
                    <div class="h-px w-10 bg-[#c9a227] opacity-50"></div>
                    <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">Our Collection</span>
                    <div class="h-px w-10 bg-[#c9a227] opacity-50"></div>
                </div>
                <h2 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">আমাদের মশলা সংগ্রহ</h2>
                <p class="text-gray-400 text-sm mt-1">সর্বোচ্চ মানের — সম্পূর্ণ প্রাকৃতিক</p>
            </div>

            {{-- Card / List toggle --}}
            <div class="flex items-center gap-1 p-1 bg-white border border-gray-200 rounded-xl shadow-sm self-start sm:self-auto">
                <button id="btn-card" onclick="setView('card')" title="Card view"
                        class="p-2 rounded-lg bg-[#14532d] text-white transition-colors" aria-pressed="true">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button id="btn-list" onclick="setView('list')" title="List view"
                        class="p-2 rounded-lg text-gray-400 hover:text-gray-600 transition-colors" aria-pressed="false">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        @if($products->isEmpty())
            <div class="text-center py-24 text-gray-400">
                <p class="text-5xl mb-4">🌿</p>
                <p class="text-lg">এই মুহূর্তে কোনো পণ্য পাওয়া যাচ্ছে না।</p>
            </div>
        @else

        {{-- ══ CARD VIEW ══ --}}
        <div id="card-view" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
            @foreach($products as $product)
            <article class="product-card bg-white rounded-2xl overflow-hidden shadow border border-green-50 flex flex-col">

                {{-- Image / placeholder --}}
                <div class="relative h-52 overflow-hidden flex-shrink-0">
                    @if($product->main_image)
                        <img src="{{ asset($product->main_image) }}" alt="{{ $product->name_bn }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center relative">
                            <div class="absolute inset-0 pointer-events-none opacity-20">
                                <div class="absolute top-3 left-3 w-14 h-14 border border-[#c9a227] rounded-full"></div>
                                <div class="absolute bottom-3 right-3 w-10 h-10 border border-[#c9a227] rounded-full"></div>
                                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-28 h-28 border border-[#c9a227] rounded-full"></div>
                            </div>
                            <div class="z-10 text-center px-4">
                                <div class="text-[#c9a227] font-serif-bn text-2xl font-bold leading-tight">{{ $product->name_bn }}</div>
                                <div class="text-green-300 text-xs mt-1 tracking-widest uppercase">{{ $product->name_en }}</div>
                            </div>
                        </div>
                    @endif
                    <span class="absolute top-3 left-3 text-[10px] font-bold px-2 py-0.5 rounded-full shadow
                        {{ $product->isInStock() ? 'bg-[#c9a227] text-[#0f3d22]' : 'bg-red-500 text-white' }}">
                        {{ $product->isInStock() ? 'স্টকে আছে' : 'স্টক শেষ' }}
                    </span>
                </div>

                {{-- Body --}}
                <div class="p-5 flex flex-col flex-1">
                    <h3 class="font-serif-bn text-[#14532d] text-xl font-bold leading-snug">{{ $product->name_bn }}</h3>
                    <p class="text-gray-400 text-[11px] tracking-widest uppercase mt-0.5">{{ $product->name_en }}</p>

                    @if($product->short_description)
                        <p class="text-gray-500 text-sm mt-2 leading-relaxed line-clamp-2">{{ $product->short_description }}</p>
                    @endif

                    @if($product->activePrices->isNotEmpty())
                        <div class="mt-3 flex items-baseline gap-1.5">
                            <span class="text-[#c9a227] font-serif-bn text-2xl font-bold">
                                ৳{{ number_format($product->activePrices->first()->final_price, 0) }}
                            </span>
                            <span class="text-gray-400 text-xs">থেকে শুরু</span>
                        </div>

                        {{-- Pack price chips --}}
                        <div class="mt-3 grid grid-cols-3 gap-1.5">
                            @foreach($product->activePrices as $price)
                                <div class="p-chip p-1.5 text-center">
                                    <div class="chip-lbl text-gray-400 text-[10px] leading-tight">{{ $price->label }}</div>
                                    <div class="chip-val text-[#14532d] text-[13px] font-semibold leading-tight mt-0.5">
                                        ৳{{ number_format($price->final_price, 0) }}
                                    </div>
                                    @if($price->is_manual_override)
                                        <div class="text-[#c9a227] text-[9px] leading-none mt-0.5">★</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex-1 min-h-3"></div>

                    <div class="mt-4 flex gap-2">
                        <button onclick="openModal({{ $product->id }})"
                                class="flex-1 bg-[#14532d] hover:bg-[#166534] text-white text-center py-2.5 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                            বিস্তারিত দেখুন
                        </button>
                        <button onclick="goToCombo({{ $product->id }})"
                                class="flex-1 border border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] py-2.5 rounded-xl text-sm font-semibold transition-colors">
                            কম্বো
                        </button>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        {{-- ══ LIST VIEW ══ --}}
        <div id="list-view">
            @foreach($products as $product)
            <article class="bg-white rounded-xl border border-green-50 shadow-sm hover:shadow-md transition-shadow flex overflow-hidden">

                {{-- Thumb --}}
                <div class="w-28 sm:w-36 flex-shrink-0 relative min-h-[110px]">
                    @if($product->main_image)
                        <img src="{{ asset($product->main_image) }}" alt="{{ $product->name_bn }}"
                             class="w-full h-full object-cover">
                    @else
                        <div class="w-full h-full bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center">
                            <div class="text-center px-2">
                                <div class="text-[#c9a227] font-serif-bn text-base font-bold leading-tight">{{ $product->name_bn }}</div>
                            </div>
                        </div>
                    @endif
                    <span class="absolute top-2 left-2 text-[9px] font-bold px-1.5 py-0.5 rounded-full
                        {{ $product->isInStock() ? 'bg-[#c9a227] text-[#0f3d22]' : 'bg-red-500 text-white' }}">
                        {{ $product->isInStock() ? '✓' : '✗' }}
                    </span>
                </div>

                {{-- Content --}}
                <div class="flex-1 p-4 flex flex-col sm:flex-row sm:items-center gap-3 min-w-0">
                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-baseline gap-2">
                            <h3 class="font-serif-bn text-[#14532d] font-bold text-lg leading-tight">{{ $product->name_bn }}</h3>
                            <span class="text-gray-400 text-xs uppercase tracking-wider">{{ $product->name_en }}</span>
                        </div>
                        @if($product->short_description)
                            <p class="text-gray-500 text-sm mt-0.5 line-clamp-1">{{ $product->short_description }}</p>
                        @endif

                        @if($product->activePrices->isNotEmpty())
                            <div class="mt-2 flex flex-wrap gap-1">
                                @foreach($product->activePrices as $price)
                                    <span class="text-[11px] bg-green-50 border border-green-100 text-[#14532d] px-2 py-0.5 rounded-full whitespace-nowrap">
                                        {{ $price->label }} · ৳{{ number_format($price->final_price, 0) }}
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Price + buttons --}}
                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 flex-shrink-0">
                        @if($product->activePrices->isNotEmpty())
                            <div class="text-right">
                                <div class="text-[#c9a227] font-bold text-xl font-serif-bn">
                                    ৳{{ number_format($product->activePrices->first()->final_price, 0) }}
                                </div>
                                <div class="text-gray-400 text-[10px]">থেকে শুরু</div>
                            </div>
                        @endif
                        <div class="flex gap-2">
                            <button onclick="openModal({{ $product->id }})"
                                    class="bg-[#14532d] hover:bg-[#166534] text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                                বিস্তারিত
                            </button>
                            <button onclick="goToCombo({{ $product->id }})"
                                    class="border border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                                কম্বো
                            </button>
                        </div>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        @endif {{-- end products not empty --}}
    </div>
</section>

<div class="gold-rule"></div>

{{-- ━━━━━━━━━━━━━━━━  COMBO BUILDER  ━━━━━━━━━━━━━━━━ --}}
<section id="combo-builder" class="py-16 md:py-20 px-5 bg-[#f6fdf8]">
    <div class="max-w-7xl mx-auto">

        {{-- Header --}}
        <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-4 mb-3">
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
                <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">Build Your Own</span>
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
            </div>
            <h2 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">নিজের কম্বো বানান</h2>
            <p class="text-gray-400 text-sm mt-2 max-w-md mx-auto leading-relaxed">
                আপনার পছন্দের মশলা বেছে নিন, পরিমাণ ঠিক করুন — আমরা পৌঁছে দেব।
            </p>
        </div>

        <div class="flex flex-col lg:flex-row gap-8 items-start">

            {{-- ── Product Picker ── --}}
            <div class="flex-1 min-w-0">
                <div class="space-y-2.5">
                @foreach($products as $product)
                    @if($product->activePrices->isNotEmpty())
                    <div id="picker-row-{{ $product->id }}"
                         class="bg-white border border-green-100 rounded-xl p-3 sm:p-4 flex flex-wrap sm:flex-nowrap items-center gap-3 shadow-sm transition-colors duration-300">

                        {{-- Initial avatar --}}
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center flex-shrink-0 shadow">
                            <span class="text-[#c9a227] font-serif-bn font-bold text-lg">{{ mb_substr($product->name_bn, 0, 1) }}</span>
                        </div>

                        {{-- Name --}}
                        <div class="flex-1 min-w-0 sm:min-w-[130px]">
                            <div class="font-serif-bn text-[#14532d] font-bold text-sm sm:text-base leading-tight">{{ $product->name_bn }}</div>
                            <div class="text-gray-400 text-[11px] uppercase tracking-wider">{{ $product->name_en }}</div>
                        </div>

                        {{-- Qty select --}}
                        <select id="picker-qty-{{ $product->id }}"
                                onchange="pickerPriceUpdate({{ $product->id }})"
                                class="border border-green-200 bg-white rounded-xl px-3 py-2 text-sm text-[#14532d] font-medium focus:outline-none focus:ring-2 focus:ring-[#14532d] w-full sm:w-auto flex-shrink-0">
                            @foreach($product->activePrices as $price)
                                <option value="{{ $price->id }}" data-price="{{ $price->final_price }}">{{ $price->label }}</option>
                            @endforeach
                        </select>

                        {{-- Price display --}}
                        <div id="picker-price-{{ $product->id }}"
                             class="text-[#c9a227] font-bold font-serif-bn text-base sm:text-lg min-w-[72px] text-right flex-shrink-0">
                            ৳{{ number_format($product->activePrices->first()->final_price, 0) }}
                        </div>

                        {{-- Add button --}}
                        <button id="picker-btn-{{ $product->id }}"
                                onclick="addToCombo({{ $product->id }})"
                                class="flex-shrink-0 bg-[#14532d] hover:bg-[#166534] text-white text-sm font-semibold px-4 py-2 rounded-xl transition-colors shadow-sm whitespace-nowrap w-full sm:w-auto">
                            + যোগ করুন
                        </button>

                    </div>
                    @endif
                @endforeach
                </div>
            </div>

            {{-- ── Combo Summary ── --}}
            <div class="w-full lg:w-80 xl:w-96 flex-shrink-0 lg:sticky lg:top-24">
                <div class="rounded-2xl shadow-xl overflow-hidden border border-green-800">

                    {{-- Header --}}
                    <div class="bg-[#14532d] px-5 py-4 flex items-center justify-between">
                        <h3 class="font-serif-bn text-[#c9a227] text-lg font-bold">আপনার কম্বো</h3>
                        <span id="combo-count-badge"
                              class="bg-[#c9a227] text-[#0f3d22] text-xs font-bold px-2.5 py-1 rounded-full"
                              style="display:none;">0</span>
                    </div>

                    {{-- Items area --}}
                    <div class="bg-[#14532d] px-5 py-3 min-h-[110px] max-h-[300px] overflow-y-auto" id="combo-items-wrap">
                        <div id="combo-empty" class="flex flex-col items-center justify-center py-5 text-center">
                            <span class="text-3xl mb-2">🌿</span>
                            <p class="text-green-400 text-sm">এখনো কোনো পণ্য যোগ করেননি।</p>
                            <p class="text-green-700 text-xs mt-1">বাম দিক থেকে পণ্য বেছে নিন।</p>
                        </div>
                        <div id="combo-list"></div>
                    </div>

                    {{-- Totals --}}
                    <div class="bg-[#0f3d22] px-5 py-4 space-y-2.5">
                        <div class="flex justify-between text-sm text-green-400">
                            <span>সাবটোটাল</span>
                            <span id="combo-subtotal">৳০</span>
                        </div>
                        <div class="flex items-center justify-between text-sm text-green-400">
                            <span class="flex items-center gap-1">
                                প্যাকেজিং চার্জ
                                <span class="text-green-700 text-[10px]">(প্রতি অর্ডার)</span>
                            </span>
                            <span id="combo-pack">৳{{ number_format($packagingCost, 0) }}</span>
                        </div>
                        <div class="h-px bg-green-800"></div>
                        <div class="flex justify-between font-bold text-[#c9a227] font-serif-bn text-lg">
                            <span>মোট</span>
                            <span id="combo-total">৳{{ number_format($packagingCost, 0) }}</span>
                        </div>
                    </div>

                    {{-- CTA --}}
                    <div class="bg-[#0f3d22] px-5 pb-5">
                        <button type="button" id="combo-order-btn"
                                onclick="openOrderForm()"
                                class="w-full bg-[#c9a227] text-[#0f3d22] font-bold py-3 rounded-xl text-sm shadow-lg opacity-40 cursor-not-allowed pointer-events-none transition-all">
                            অর্ডার দিন →
                        </button>
                        <p class="text-green-700 text-[10px] text-center mt-2 leading-relaxed">
                            পণ্য যোগ করুন · তারপর অর্ডার দিন
                        </p>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<div class="gold-rule"></div>

{{-- ━━━━━━━━━━━━━━━━  WHY US  ━━━━━━━━━━━━━━━━ --}}
<section id="why-us" class="bg-[#14532d] py-16 md:py-20 px-5">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center gap-4 mb-3">
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
                <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">Why Choose Us</span>
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
            </div>
            <h2 class="font-serif-bn text-[#fef9ee] text-3xl md:text-4xl font-bold">কেন আমাদের বেছে নেবেন?</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-8">
            @foreach([
                ['🌱', '১০০% খাঁটি',     'কোনো কৃত্রিম রং বা সংরক্ষক ছাড়া সরাসরি উৎস থেকে সংগৃহীত।'],
                ['📦', 'নিরাপদ প্যাকেজিং','বায়ুরোধী প্যাকেজিংয়ে মশলার সতেজতা দীর্ঘদিন বজায় থাকে।'],
                ['🚚', 'দ্রুত ডেলিভারি',  'সারা বাংলাদেশে দ্রুত ও নিরাপদ হোম ডেলিভারির ব্যবস্থা।'],
                ['💬', 'সহজ অর্ডার',      'অনলাইনে বা ফোনে অর্ডার করুন, যেকোনো সময় যেকোনো স্থান থেকে।'],
            ] as [$icon, $title, $desc])
            <div class="text-center group">
                <div class="w-16 h-16 rounded-full border-2 border-[#c9a227] border-opacity-30 group-hover:border-opacity-100 transition-all bg-[#0f3d22] flex items-center justify-center mx-auto mb-4">
                    <span class="text-3xl">{{ $icon }}</span>
                </div>
                <h3 class="text-[#c9a227] font-semibold text-base mb-2">{{ $title }}</h3>
                <p class="text-green-300 text-sm leading-relaxed">{{ $desc }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ━━━━━━━━━━━━━━━━  CONTACT  ━━━━━━━━━━━━━━━━ --}}
<section id="contact" class="bg-[#fef9ee] py-12 px-5 border-t border-amber-100">
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="font-serif-bn text-[#14532d] text-2xl md:text-3xl font-bold mb-2">অর্ডার করুন এখনই</h2>
        <p class="text-gray-500 text-sm mb-6">ফোনে বা WhatsApp-এ যোগাযোগ করুন — আমরা সর্বদা প্রস্তুত</p>
        <a href="tel:+8801700000000"
           class="inline-flex items-center gap-2 bg-[#14532d] text-[#fef9ee] font-semibold px-8 py-3.5 rounded-full hover:bg-[#166534] transition-colors shadow-lg text-base">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
            ০১৭০০-০০০০০০
        </a>
    </div>
</section>

{{-- ━━━━━━━━━━━━━━━━  FOOTER  ━━━━━━━━━━━━━━━━ --}}
<footer class="bg-[#0f3d22] py-10 px-5">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h3 class="font-serif-bn text-[#c9a227] text-xl font-bold">মসলা ঘর</h3>
                <p class="text-green-500 text-xs mt-1">খাঁটি মশলার আস্থার দোকান</p>
            </div>
            <div class="flex gap-6 text-green-500 text-xs">
                <a href="#products" class="hover:text-[#c9a227] transition-colors">পণ্যসমূহ</a>
                <a href="#why-us"   class="hover:text-[#c9a227] transition-colors">আমাদের সম্পর্কে</a>
                <a href="#contact"  class="hover:text-[#c9a227] transition-colors">যোগাযোগ</a>
            </div>
        </div>
        <div class="gold-rule mt-8 mb-6 opacity-20"></div>
        <p class="text-center text-green-700 text-xs">&copy; {{ date('Y') }} মসলা ঘর — সমস্ত অধিকার সংরক্ষিত।</p>
    </div>
</footer>

{{-- ━━━━━━━━━━━━━━━━  PRODUCT MODAL  ━━━━━━━━━━━━━━━━ --}}

{{-- Backdrop --}}
<div id="modal-overlay" class="fixed inset-0 z-[100] bg-black/70" style="display:none;"></div>

{{-- Scroll container --}}
<div id="modal-wrapper" class="fixed inset-0 z-[101] overflow-y-auto" style="display:none;">
    <div class="flex min-h-full items-start sm:items-center justify-center p-4 py-8">

        <div id="modal-panel" class="modal-enter relative bg-[#fef9ee] rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">

            {{-- ── Media area ── --}}
            <div id="modal-media" class="relative w-full overflow-hidden bg-gradient-to-br from-[#14532d] to-[#1a6b3a]"
                 style="height:260px;">

                {{-- Real image --}}
                <img id="modal-img" src="" alt=""
                     class="absolute inset-0 w-full h-full object-cover" style="display:none;">

                {{-- YouTube iframe --}}
                <iframe id="modal-video" src="" frameborder="0"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                        class="absolute inset-0 w-full h-full" style="display:none;"></iframe>

                {{-- Decorative placeholder --}}
                <div id="modal-ph" class="absolute inset-0 flex items-center justify-center">
                    <div class="absolute inset-0 pointer-events-none opacity-15">
                        <div class="absolute top-5 left-5 w-24 h-24 border border-[#c9a227] rounded-full"></div>
                        <div class="absolute bottom-5 right-5 w-16 h-16 border border-[#c9a227] rounded-full"></div>
                        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-44 h-44 border border-[#c9a227] rounded-full"></div>
                    </div>
                    <div class="z-10 text-center">
                        <div id="modal-ph-bn" class="text-[#c9a227] font-serif-bn text-4xl font-bold"></div>
                        <div id="modal-ph-en" class="text-green-300 text-sm mt-1 tracking-widest uppercase"></div>
                    </div>
                </div>

                {{-- Close button (top-right, always on top of media) --}}
                <button id="modal-close" onclick="closeModal()"
                        class="absolute top-3 right-3 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center text-xl leading-none transition-colors"
                        aria-label="close">&times;</button>
            </div>

            {{-- ── Content area ── --}}
            <div id="modal-body" class="p-6 max-h-[60vh] overflow-y-auto">

                {{-- Header row --}}
                <div class="flex items-start justify-between gap-3 mb-3">
                    <div>
                        <h2 id="modal-name-bn" class="font-serif-bn text-[#14532d] text-2xl sm:text-3xl font-bold leading-tight"></h2>
                        <p id="modal-name-en" class="text-gray-400 text-xs tracking-widest uppercase mt-0.5"></p>
                    </div>
                    <span id="modal-stock-badge" class="flex-shrink-0 text-xs font-bold px-3 py-1 rounded-full mt-1"></span>
                </div>

                <div class="gold-rule mb-4"></div>

                {{-- Short description --}}
                <p id="modal-short-desc" class="text-gray-600 text-sm leading-relaxed"></p>

                {{-- Full description (expandable) --}}
                <div id="modal-desc-wrap" style="display:none;" class="mt-2">
                    <button onclick="toggleDesc()" class="text-[#14532d] text-xs font-semibold hover:underline flex items-center gap-1 mt-1">
                        <span id="modal-desc-lbl">বিস্তারিত পড়ুন ▾</span>
                    </button>
                    <p id="modal-desc" class="text-gray-500 text-sm leading-relaxed mt-2 pl-3 border-l-2 border-green-200"
                       style="display:none;"></p>
                </div>

                {{-- Prices grid --}}
                <div class="mt-5">
                    <h4 class="text-[#14532d] text-sm font-semibold mb-2 flex items-center gap-2">
                        পরিমাণ ও দাম
                        <span class="flex-1 h-px bg-green-100 inline-block"></span>
                    </h4>
                    <div id="modal-prices-grid" class="grid grid-cols-3 sm:grid-cols-6 gap-2"></div>
                </div>

                {{-- Quantity selector --}}
                <div class="mt-5">
                    <h4 class="text-[#14532d] text-sm font-semibold mb-2 flex items-center gap-2">
                        পরিমাণ বেছে নিন
                        <span class="flex-1 h-px bg-green-100 inline-block"></span>
                    </h4>
                    <div class="flex items-center gap-3">
                        <select id="modal-qty-sel"
                                onchange="onQtyChange(this.value)"
                                class="flex-1 border border-green-200 bg-white rounded-xl px-4 py-2.5 text-sm text-[#14532d] font-medium focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            <option value="">— পরিমাণ বেছে নিন —</option>
                        </select>
                        <div id="modal-sel-price" class="flex-shrink-0 text-[#c9a227] font-serif-bn text-2xl font-bold min-w-[70px] text-right">
                            ——
                        </div>
                    </div>
                </div>

                {{-- Action buttons --}}
                <div class="flex gap-3 mt-6">
                    <button disabled title="শীঘ্রই আসছে"
                            class="flex-1 border-2 border-[#c9a227] text-[#c9a227] py-3 rounded-xl text-sm font-bold opacity-50 cursor-not-allowed">
                        কম্বো তে যোগ করুন
                    </button>
                    <a href="#contact" onclick="closeModal()"
                       class="flex-1 bg-[#14532d] hover:bg-[#166534] text-[#fef9ee] py-3 rounded-xl text-sm font-bold text-center transition-colors shadow">
                        অর্ডার করুন
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ━━━━━━━━━━━━━━━━  ORDER FORM MODAL  ━━━━━━━━━━━━━━━━ --}}
<div id="order-overlay" class="fixed inset-0 z-[120] bg-black/75" style="display:none;"></div>
<div id="order-wrapper" class="fixed inset-0 z-[121] overflow-y-auto" style="display:none;">
    <div class="flex min-h-full items-start justify-center p-4 py-8">
        <div class="modal-enter relative bg-[#fef9ee] rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">

            {{-- Header --}}
            <div class="bg-[#14532d] px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="font-serif-bn text-[#c9a227] text-xl font-bold">অর্ডার ফর্ম</h2>
                    <p class="text-green-400 text-xs mt-0.5" id="order-hdr-payment-method">পেমেন্ট পদ্ধতি বেছে নিন</p>
                </div>
                <button onclick="closeOrderForm()"
                        class="w-8 h-8 rounded-full bg-black/30 hover:bg-black/50 text-white flex items-center justify-center text-xl leading-none transition-colors"
                        aria-label="close">&times;</button>
            </div>

            {{-- Combo summary (read-only) --}}
            <div class="bg-[#f0faf4] px-6 py-4 border-b border-green-100">
                <h4 class="text-[#14532d] text-[11px] font-bold uppercase tracking-wider mb-2">আপনার কম্বো</h4>
                <div id="order-items-preview" class="space-y-1.5 text-sm max-h-32 overflow-y-auto"></div>
                <div class="flex justify-between items-center font-bold text-[#14532d] pt-2.5 mt-2 border-t border-green-200">
                    <span class="text-sm">মোট (প্যাকেজিংসহ)</span>
                    <span id="order-total-display" class="text-[#c9a227] font-serif-bn text-xl"></span>
                </div>
            </div>

            {{-- General / items error --}}
            <div id="order-general-error" class="hidden bg-red-50 border-l-4 border-red-400 px-5 py-3">
                <p class="text-red-700 text-sm font-medium" id="order-general-error-text"></p>
            </div>

            {{-- Customer form --}}
            <form id="order-form" action="{{ route('order.store') }}" method="POST">
                @csrf
                {{-- Hidden combo item fields populated by JS --}}
                <div id="order-items-hidden"></div>

                <div class="px-6 py-5 space-y-4">

                    {{-- full_name --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                            পূর্ণ নাম <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="full_name" id="f-full_name"
                               placeholder="আপনার পূর্ণ নাম লিখুন"
                               class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                        <p id="err-full_name" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    {{-- mobile + alternative --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                মোবাইল নম্বর <span class="text-red-400">*</span>
                            </label>
                            <input type="tel" name="mobile_number" id="f-mobile_number"
                                   placeholder="01XXXXXXXXX"
                                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                            <p id="err-mobile_number" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                বিকল্প নম্বর
                            </label>
                            <input type="tel" name="alternative_number" id="f-alternative_number"
                                   placeholder="01XXXXXXXXX (ঐচ্ছিক)"
                                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                            <p id="err-alternative_number" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    </div>

                    {{-- full_address --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                            পূর্ণ ঠিকানা <span class="text-red-400">*</span>
                        </label>
                        <textarea name="full_address" id="f-full_address" rows="2"
                                  placeholder="বাড়ি/ফ্ল্যাট নম্বর, রাস্তা, মহল্লা..."
                                  class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white resize-none"></textarea>
                        <p id="err-full_address" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    {{-- district + area --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                জেলা <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="district" id="f-district"
                                   placeholder="যেমন: ঢাকা"
                                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                            <p id="err-district" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                        <div>
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                এলাকা <span class="text-red-400">*</span>
                            </label>
                            <input type="text" name="area" id="f-area"
                                   placeholder="যেমন: মিরপুর-১০"
                                   class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                            <p id="err-area" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>
                    </div>

                    {{-- order_note --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                            অর্ডার নোট
                        </label>
                        <textarea name="order_note" id="f-order_note" rows="2"
                                  placeholder="বিশেষ নির্দেশনা থাকলে লিখুন (ঐচ্ছিক)"
                                  class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white resize-none"></textarea>
                    </div>

                    <p id="err-items" class="text-red-500 text-xs hidden"></p>

                    {{-- Payment Method --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-2">
                            পেমেন্ট পদ্ধতি <span class="text-red-400">*</span>
                        </label>
                        <div class="grid grid-cols-2 gap-2" id="payment-method-grid">
                            {{-- Rendered by JS based on PAYMENT_SETTINGS.enabled_methods --}}
                        </div>
                        <p id="err-payment_method" class="text-red-500 text-xs mt-1 hidden"></p>

                        {{-- Payment info (number + instruction) --}}
                        <div id="payment-info-panel" style="display:none"
                             class="mt-3 bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm">
                            <p id="payment-info-number" class="font-bold text-[#14532d]"></p>
                            <p id="payment-info-instruction" class="text-gray-600 text-xs mt-1"></p>
                        </div>

                        {{-- Transaction fields (shown only for manual payment) --}}
                        <div id="payment-tx-fields" style="display:none" class="mt-3 space-y-3">
                            <div>
                                <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                    সেন্ডার নম্বর <span class="text-red-400">*</span>
                                </label>
                                <input type="text" name="sender_number" id="f-sender_number"
                                       placeholder="যে নম্বর থেকে পাঠিয়েছেন"
                                       class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                                <p id="err-sender_number" class="text-red-500 text-xs mt-1 hidden"></p>
                            </div>
                            <div>
                                <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                    ট্রানজেকশন আইডি <span class="text-red-400">*</span>
                                </label>
                                <input type="text" name="transaction_id" id="f-transaction_id"
                                       placeholder="TrxID / Ref নম্বর"
                                       class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                                <p id="err-transaction_id" class="text-red-500 text-xs mt-1 hidden"></p>
                            </div>
                            <div>
                                <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                    পেমেন্ট করা পরিমাণ (৳) <span class="text-red-400">*</span>
                                </label>
                                <input type="number" name="paid_amount" id="f-paid_amount"
                                       placeholder="যত টাকা পাঠিয়েছেন" min="0" step="1"
                                       class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white">
                                <p id="err-paid_amount" class="text-red-500 text-xs mt-1 hidden"></p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Submit --}}
                <div class="px-6 pb-6">
                    <button type="submit" id="order-submit-btn"
                            class="w-full bg-[#14532d] hover:bg-[#166534] text-[#fef9ee] font-bold py-3.5 rounded-xl text-base shadow-lg transition-colors">
                        অর্ডার দিন →
                    </button>
                    <p class="text-gray-400 text-[10px] text-center mt-2">
                        অর্ডার নিশ্চিত হলে আমরা কনফার্মেশনের জন্য কল করব।
                    </p>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ━━━━━━━━━━━━━━━━  MOBILE COMBO BAR  ━━━━━━━━━━━━━━━━ --}}
<div id="combo-bar" class="fixed bottom-0 inset-x-0 z-50 lg:hidden" style="display:none;">
    <div class="bg-[#14532d] border-t-2 border-[#c9a227] px-4 py-3 shadow-2xl">
        <div class="flex items-center justify-between gap-3 max-w-lg mx-auto">
            <div class="min-w-0">
                <div id="combo-bar-count" class="text-[#c9a227] text-xs font-semibold leading-tight"></div>
                <div id="combo-bar-text" class="text-[#fef9ee] font-serif-bn font-bold text-xl leading-tight">৳০</div>
            </div>
            <a href="#combo-builder"
               class="flex-shrink-0 bg-[#c9a227] hover:bg-[#e2bb45] text-[#0f3d22] font-bold text-sm px-5 py-2.5 rounded-xl whitespace-nowrap transition-colors shadow">
                কম্বো দেখুন →
            </a>
        </div>
    </div>
</div>

{{-- ━━━━━━━━━━━━━━━━  SCRIPTS  ━━━━━━━━━━━━━━━━ --}}
<script>
// ── Product data (server-rendered JSON, keyed by id) ──────────────────────
const PRODUCTS          = @json($productsForJs);
const PACKAGING_COST    = {{ (int) $packagingCost }};
const PAYMENT_SETTINGS  = {
    bkash_number:        @json($paymentSettings->bkash_number),
    rocket_number:       @json($paymentSettings->rocket_number),
    nagad_number:        @json($paymentSettings->nagad_number),
    payment_instruction: @json($paymentSettings->payment_instruction),
    enabled_methods:     @json($paymentSettings->enabledMethods()),
};

let currentId = null;

// ── Modal open/close ──────────────────────────────────────────────────────
function openModal(id) {
    const p = PRODUCTS[id];
    if (!p) return;
    currentId = id;
    fillModal(p);
    document.getElementById('modal-overlay').style.display = 'block';
    document.getElementById('modal-wrapper').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-wrapper').style.display = 'none';
    document.getElementById('modal-video').src = '';       // stop video
    document.body.style.overflow = '';
    currentId = null;
}

// ── Populate modal ────────────────────────────────────────────────────────
function fillModal(p) {
    // Names
    document.getElementById('modal-name-bn').textContent = p.name_bn;
    document.getElementById('modal-name-en').textContent = p.name_en;
    document.getElementById('modal-ph-bn').textContent   = p.name_bn;
    document.getElementById('modal-ph-en').textContent   = p.name_en;

    // Media
    const imgEl = document.getElementById('modal-img');
    const vidEl = document.getElementById('modal-video');
    const phEl  = document.getElementById('modal-ph');
    [imgEl, vidEl, phEl].forEach(el => el.style.display = 'none');

    const ytId = ytExtract(p.video_url);
    if (ytId) {
        vidEl.src = 'https://www.youtube.com/embed/' + ytId + '?rel=0';
        vidEl.style.display = 'block';
    } else if (p.main_image) {
        imgEl.src = p.main_image;
        imgEl.alt = p.name_bn;
        imgEl.style.display = 'block';
    } else {
        phEl.style.display = 'flex';
    }

    // Stock badge
    const badge = document.getElementById('modal-stock-badge');
    if (p.stock > 0) {
        badge.textContent  = 'স্টকে আছে';
        badge.style.cssText = 'background:#c9a227;color:#0f3d22;';
    } else {
        badge.textContent  = 'স্টক শেষ';
        badge.style.cssText = 'background:#ef4444;color:#fff;';
    }

    // Descriptions
    document.getElementById('modal-short-desc').textContent = p.short_description || '';
    const dw  = document.getElementById('modal-desc-wrap');
    const dtx = document.getElementById('modal-desc');
    const dlb = document.getElementById('modal-desc-lbl');
    if (p.description && p.description !== p.short_description) {
        document.getElementById('modal-desc').textContent = p.description;
        dtx.style.display = 'none';
        dlb.textContent   = 'বিস্তারিত পড়ুন ▾';
        dw.style.display  = 'block';
    } else {
        dw.style.display = 'none';
    }

    // Prices grid
    const grid = document.getElementById('modal-prices-grid');
    grid.innerHTML = p.prices.map(pr => `
        <div id="mchip-${pr.id}" class="p-chip p-2 text-center"
             onclick="selectChip(${pr.id})">
            <div class="chip-lbl text-gray-400 text-[10px] leading-tight">${pr.label}</div>
            <div class="chip-val text-[#14532d] text-sm font-bold leading-tight mt-0.5">৳${fmt(pr.final_price)}</div>
            ${pr.is_manual_override ? '<div style="color:#c9a227;font-size:9px;">★</div>' : ''}
        </div>
    `).join('');

    // Quantity dropdown
    const sel = document.getElementById('modal-qty-sel');
    sel.innerHTML = '<option value="">— পরিমাণ বেছে নিন —</option>'
        + p.prices.map(pr =>
            `<option value="${pr.id}">৳${fmt(pr.final_price)} — ${pr.label}</option>`
        ).join('');
    document.getElementById('modal-sel-price').textContent = '——';
}

// ── Price chip selection ──────────────────────────────────────────────────
function selectChip(priceId) {
    const p = PRODUCTS[currentId];
    if (!p) return;
    const price = p.prices.find(x => x.id === priceId);
    if (!price) return;

    // Reset all chips
    p.prices.forEach(x => {
        const el = document.getElementById('mchip-' + x.id);
        if (!el) return;
        el.classList.remove('active');
        el.style.background    = '';
        el.style.borderColor   = '';
        el.querySelector('.chip-lbl').style.color = '';
        el.querySelector('.chip-val').style.color = '';
    });

    // Activate selected chip
    const chip = document.getElementById('mchip-' + priceId);
    if (chip) {
        chip.style.background  = '#14532d';
        chip.style.borderColor = '#14532d';
        chip.querySelector('.chip-lbl').style.color = '#86efac';
        chip.querySelector('.chip-val').style.color = '#fff';
    }

    // Sync dropdown and price display
    document.getElementById('modal-qty-sel').value      = priceId;
    document.getElementById('modal-sel-price').textContent = '৳' + fmt(price.final_price);
}

function onQtyChange(val) {
    const id = parseInt(val, 10);
    if (!id) { document.getElementById('modal-sel-price').textContent = '——'; return; }
    selectChip(id);
}

// ── Description toggle ────────────────────────────────────────────────────
function toggleDesc() {
    const el  = document.getElementById('modal-desc');
    const lbl = document.getElementById('modal-desc-lbl');
    const vis = el.style.display !== 'none';
    el.style.display  = vis ? 'none' : 'block';
    lbl.textContent   = vis ? 'বিস্তারিত পড়ুন ▾' : 'লুকিয়ে রাখুন ▴';
}

// ── View toggle (card / list) ─────────────────────────────────────────────
function setView(view) {
    const cv   = document.getElementById('card-view');
    const lv   = document.getElementById('list-view');
    const bc   = document.getElementById('btn-card');
    const bl   = document.getElementById('btn-list');
    const on   = 'p-2 rounded-lg bg-[#14532d] text-white transition-colors';
    const off  = 'p-2 rounded-lg text-gray-400 hover:text-gray-600 transition-colors';

    if (view === 'card') {
        cv.style.display = '';        // restored by grid CSS class
        lv.style.display = 'none';
        bc.className = on;  bc.setAttribute('aria-pressed','true');
        bl.className = off; bl.setAttribute('aria-pressed','false');
    } else {
        cv.style.display = 'none';
        lv.style.display = 'flex';
        bc.className = off; bc.setAttribute('aria-pressed','false');
        bl.className = on;  bl.setAttribute('aria-pressed','true');
    }
    try { localStorage.setItem('msv', view); } catch(e) {}
}

// Restore saved view
try { if (localStorage.getItem('msv') === 'list') setView('list'); } catch(e) {}

// ── Helpers ───────────────────────────────────────────────────────────────
function fmt(v)  { return Math.round(parseFloat(v)).toLocaleString('bn-BD'); }
function ytExtract(url) {
    if (!url) return null;
    const m = url.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
    return m ? m[1] : null;
}

// ── Event listeners ───────────────────────────────────────────────────────
document.getElementById('modal-close').addEventListener('click', closeModal);
document.getElementById('modal-overlay').addEventListener('click', closeModal);
document.addEventListener('keydown', e => { if (e.key === 'Escape') { closeModal(); closeOrderForm(); } });

// Mobile nav
(function () {
    const toggle = document.getElementById('nav-toggle');
    const menu   = document.getElementById('mobile-menu');
    const open   = document.getElementById('ico-open');
    const close  = document.getElementById('ico-close');
    toggle.addEventListener('click', () => {
        const vis = menu.style.display === 'flex';
        menu.style.display = vis ? 'none' : 'flex';
        open.classList.toggle('hidden', !vis);
        close.classList.toggle('hidden', vis);
    });
    menu.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        menu.style.display = 'none';
        open.classList.remove('hidden');
        close.classList.add('hidden');
    }));
})();

// Smooth scroll
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const t = document.querySelector(a.getAttribute('href'));
        if (t) { e.preventDefault(); t.scrollIntoView({ behavior: 'smooth' }); }
    });
});

// ── Combo Builder ─────────────────────────────────────────────────────────
let comboItems = [];
let comboUid   = 0;

function pickerPriceUpdate(productId) {
    const sel = document.getElementById('picker-qty-' + productId);
    if (!sel) return;
    const opt = sel.options[sel.selectedIndex];
    const price = opt ? parseFloat(opt.dataset.price) : 0;
    const el = document.getElementById('picker-price-' + productId);
    if (el) el.textContent = '৳' + fmt(price);
}

function addToCombo(productId) {
    const p = PRODUCTS[productId];
    if (!p) return;
    const sel = document.getElementById('picker-qty-' + productId);
    if (!sel) return;
    const priceId = parseInt(sel.value, 10);
    const price   = p.prices.find(x => x.id === priceId);
    if (!price) return;

    // Duplicate → flash existing item in summary
    const dup = comboItems.find(x => x.productId === productId && x.priceId === priceId);
    if (dup) { flashComboItem(dup.uid); return; }

    comboUid++;
    comboItems.push({ uid: comboUid, productId, priceId, quantity_gram: price.quantity_gram, nameBn: p.name_bn, label: price.label, price: price.final_price });

    // Confirm flash on add button
    const btn = document.getElementById('picker-btn-' + productId);
    if (btn) {
        const orig = btn.textContent;
        btn.textContent = '✓ যোগ হয়েছে';
        btn.style.background = '#16a34a';
        setTimeout(() => { btn.textContent = orig; btn.style.background = ''; }, 1400);
    }

    renderCombo();
}

function removeFromCombo(uid) {
    comboItems = comboItems.filter(x => x.uid !== uid);
    renderCombo();
}

function changeComboItem(uid, newPriceIdStr) {
    const item = comboItems.find(x => x.uid === uid);
    if (!item) return;
    const p     = PRODUCTS[item.productId];
    const price = p && p.prices.find(x => x.id === parseInt(newPriceIdStr, 10));
    if (!price) return;
    item.priceId       = price.id;
    item.quantity_gram = price.quantity_gram;
    item.label         = price.label;
    item.price         = price.final_price;
    renderCombo();
}

function flashComboItem(uid) {
    const el = document.getElementById('citem-' + uid);
    if (!el) return;
    el.style.transition = 'background .15s';
    el.style.background = 'rgba(201,162,39,.35)';
    setTimeout(() => { el.style.background = ''; }, 900);
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function renderCombo() {
    const listEl   = document.getElementById('combo-list');
    const emptyEl  = document.getElementById('combo-empty');
    const subEl    = document.getElementById('combo-subtotal');
    const totalEl  = document.getElementById('combo-total');
    const badgeEl  = document.getElementById('combo-count-badge');
    const barEl    = document.getElementById('combo-bar');
    const barCount = document.getElementById('combo-bar-count');
    const barText  = document.getElementById('combo-bar-text');
    const orderBtn = document.getElementById('combo-order-btn');

    const n = comboItems.length;
    emptyEl.style.display = n ? 'none' : 'flex';
    badgeEl.style.display = n ? 'inline-block' : 'none';
    badgeEl.textContent   = n;

    // Build item rows
    listEl.innerHTML = comboItems.map(item => {
        const p    = PRODUCTS[item.productId];
        const opts = p.prices.map(pr =>
            `<option value="${pr.id}"${pr.id === item.priceId ? ' selected' : ''}>${pr.label} · ৳${fmt(pr.final_price)}</option>`
        ).join('');
        return `
        <div id="citem-${item.uid}" class="flex items-center gap-2 py-2.5 border-b border-green-700/30 last:border-0 transition-colors">
            <div class="flex-1 min-w-0">
                <div class="text-[#fef9ee] font-serif-bn text-sm font-semibold leading-tight truncate">${item.nameBn}</div>
                <select onchange="changeComboItem(${item.uid},this.value)"
                        class="mt-1 text-[11px] border border-green-600 rounded-lg px-2 py-1 bg-[#1a6b3a] text-green-200 w-full focus:outline-none focus:ring-1 focus:ring-[#c9a227]">
                    ${opts}
                </select>
            </div>
            <div class="text-[#c9a227] font-bold font-serif-bn text-sm flex-shrink-0 min-w-[52px] text-right">৳${fmt(item.price)}</div>
            <button onclick="removeFromCombo(${item.uid})"
                    class="flex-shrink-0 w-6 h-6 rounded-full bg-green-800 hover:bg-red-900 text-green-300 hover:text-red-300 flex items-center justify-center text-base leading-none transition-colors">&times;</button>
        </div>`;
    }).join('');

    // Totals
    const sub   = comboItems.reduce((s, x) => s + x.price, 0);
    const grand = sub + PACKAGING_COST;
    subEl.textContent   = '৳' + fmt(sub);
    totalEl.textContent = '৳' + fmt(n > 0 ? grand : PACKAGING_COST);

    // Order button active/disabled
    if (orderBtn) {
        if (n > 0) {
            orderBtn.classList.remove('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        } else {
            orderBtn.classList.add('opacity-40', 'cursor-not-allowed', 'pointer-events-none');
        }
    }

    // Mobile sticky bar
    if (barEl) {
        if (n > 0) {
            barEl.style.display = 'block';
            document.body.style.paddingBottom = '76px';
            if (barCount) barCount.textContent = n + ' টি পণ্য বেছেছেন';
            if (barText)  barText.textContent  = '৳' + fmt(grand);
        } else {
            barEl.style.display = 'none';
            document.body.style.paddingBottom = '';
        }
    }
}

function goToCombo(productId) {
    const sec = document.getElementById('combo-builder');
    if (sec) sec.scrollIntoView({ behavior: 'smooth' });
    setTimeout(() => {
        const row = document.getElementById('picker-row-' + productId);
        if (!row) return;
        row.classList.add('picker-highlight');
        setTimeout(() => row.classList.remove('picker-highlight'), 1600);
    }, 500);
}

// ── Order Form ────────────────────────────────────────────────────────────
function openOrderForm() {
    if (comboItems.length === 0) return;

    // Build read-only combo preview
    const preview = document.getElementById('order-items-preview');
    if (preview) {
        preview.innerHTML = comboItems.map(item =>
            `<div class="flex justify-between items-baseline gap-2">
                <span class="text-gray-700 font-serif-bn font-medium truncate">${item.nameBn}
                    <span class="text-gray-400 text-[11px] font-sans">(${item.label})</span>
                </span>
                <span class="text-[#c9a227] font-bold font-serif-bn flex-shrink-0">৳${fmt(item.price)}</span>
            </div>`
        ).join('');
    }

    // Grand total display
    const sub   = comboItems.reduce((s, x) => s + x.price, 0);
    const grand = sub + PACKAGING_COST;
    const tel   = document.getElementById('order-total-display');
    if (tel) tel.textContent = '৳' + fmt(grand);

    clearOrderErrors();
    initPaymentMethods();

    document.getElementById('order-overlay').style.display = 'block';
    document.getElementById('order-wrapper').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeOrderForm() {
    const ov = document.getElementById('order-overlay');
    const wr = document.getElementById('order-wrapper');
    if (!ov || ov.style.display === 'none') return;
    ov.style.display = 'none';
    wr.style.display = 'none';
    document.body.style.overflow = '';
}

function clearOrderErrors() {
    ['full_name','mobile_number','alternative_number','full_address','district','area','items',
     'payment_method','sender_number','transaction_id','paid_amount'].forEach(f => {
        const el = document.getElementById('err-' + f);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    });
    const ge = document.getElementById('order-general-error');
    if (ge) ge.classList.add('hidden');
}

// ── Payment Method UI ─────────────────────────────────────────────────────
const PM_LABELS = {
    cash_on_delivery: '💵 ক্যাশ অন ডেলিভারি',
    bkash:            '🔴 বিকাশ',
    rocket:           '🟣 রকেট',
    nagad:            '🟠 নগদ',
};
const PM_NAMES = {
    cash_on_delivery: 'ক্যাশ অন ডেলিভারি',
    bkash:            'বিকাশ',
    rocket:           'রকেট',
    nagad:            'নগদ',
};
const MANUAL_METHODS = ['bkash', 'rocket', 'nagad'];

function initPaymentMethods() {
    const grid    = document.getElementById('payment-method-grid');
    const methods = PAYMENT_SETTINGS.enabled_methods || ['cash_on_delivery'];
    if (!grid) return;

    grid.innerHTML = methods.map(m => `
        <label for="pm-${m}" class="block cursor-pointer">
            <input type="radio" name="payment_method" id="pm-${m}" value="${m}" class="sr-only"
                   onchange="onPaymentMethodChange('${m}')">
            <div id="pm-display-${m}"
                 class="border-2 border-green-200 rounded-xl px-3 py-2.5 text-center text-xs font-semibold text-[#14532d] bg-white hover:border-[#14532d] transition-colors">
                ${PM_LABELS[m] || m}
            </div>
        </label>
    `).join('');

    // Auto-select if only one method available
    if (methods.length === 1) {
        const radio = document.getElementById('pm-' + methods[0]);
        if (radio) { radio.checked = true; onPaymentMethodChange(methods[0]); }
    }
}

function onPaymentMethodChange(method) {
    // Update header subtitle
    const hdr = document.getElementById('order-hdr-payment-method');
    if (hdr) hdr.textContent = PM_NAMES[method] || method;

    // Update button styles
    const methods = PAYMENT_SETTINGS.enabled_methods || ['cash_on_delivery'];
    methods.forEach(m => {
        const d = document.getElementById('pm-display-' + m);
        if (!d) return;
        if (m === method) {
            d.style.background   = '#14532d';
            d.style.borderColor  = '#14532d';
            d.style.color        = '#fef9ee';
        } else {
            d.style.background   = '';
            d.style.borderColor  = '';
            d.style.color        = '';
        }
    });

    const isManual   = MANUAL_METHODS.includes(method);
    const infoPanel  = document.getElementById('payment-info-panel');
    const txFields   = document.getElementById('payment-tx-fields');
    const numEl      = document.getElementById('payment-info-number');
    const instEl     = document.getElementById('payment-info-instruction');

    if (isManual) {
        const numbers = { bkash: PAYMENT_SETTINGS.bkash_number, rocket: PAYMENT_SETTINGS.rocket_number, nagad: PAYMENT_SETTINGS.nagad_number };
        const num = numbers[method];
        if (numEl) numEl.textContent = num ? (PM_NAMES[method] + ' নম্বর: ' + num) : (PM_NAMES[method] + ' নম্বরে পাঠান');
        if (instEl) instEl.textContent = PAYMENT_SETTINGS.payment_instruction || '';
        if (infoPanel) infoPanel.style.display = 'block';
        if (txFields)  txFields.style.display  = 'block';
    } else {
        if (infoPanel) infoPanel.style.display = 'none';
        if (txFields)  txFields.style.display  = 'none';
    }
}

function populateOrderItems() {
    const container = document.getElementById('order-items-hidden');
    if (!container) return;
    container.innerHTML = comboItems.map((item, i) =>
        `<input type="hidden" name="items[${i}][product_id]" value="${item.productId}">
         <input type="hidden" name="items[${i}][quantity_gram]" value="${item.quantity_gram}">`
    ).join('');
}

// Order form submit (AJAX — preserves combo state on validation error)
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('order-overlay')?.addEventListener('click', closeOrderForm);

    const form = document.getElementById('order-form');
    if (!form) return;

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        clearOrderErrors();

        if (comboItems.length === 0) {
            const el = document.getElementById('err-items');
            if (el) { el.textContent = 'কমপক্ষে একটি পণ্য যোগ করুন।'; el.classList.remove('hidden'); }
            return;
        }

        // Validate payment method selected
        const selectedMethod = form.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            const pmErr = document.getElementById('err-payment_method');
            if (pmErr) { pmErr.textContent = 'একটি পেমেন্ট পদ্ধতি বেছে নিন।'; pmErr.classList.remove('hidden'); }
            return;
        }

        populateOrderItems();

        const btn = document.getElementById('order-submit-btn');
        btn.disabled    = true;
        btn.textContent = 'প্রসেস হচ্ছে...';

        try {
            const res  = await fetch(form.action, {
                method:  'POST',
                headers: {
                    'Accept':         'application/json',
                    'X-CSRF-TOKEN':   document.querySelector('meta[name="csrf-token"]').content,
                },
                body: new FormData(form),
            });

            const data = await res.json();

            if (res.ok && data.success) {
                window.location.href = data.redirect;
                return;
            }

            // Validation errors (HTTP 422)
            if (data.errors) {
                Object.entries(data.errors).forEach(([key, msgs]) => {
                    const baseKey = key.split('.')[0];
                    const el = document.getElementById('err-' + baseKey);
                    if (el) { el.textContent = msgs[0]; el.classList.remove('hidden'); }
                });
            } else if (data.message) {
                const ge = document.getElementById('order-general-error');
                const gt = document.getElementById('order-general-error-text');
                if (ge && gt) { gt.textContent = data.message; ge.classList.remove('hidden'); }
            }
        } catch (_) {
            const ge = document.getElementById('order-general-error');
            const gt = document.getElementById('order-general-error-text');
            if (ge && gt) { gt.textContent = 'নেটওয়ার্ক সমস্যা হয়েছে। আবার চেষ্টা করুন।'; ge.classList.remove('hidden'); }
        }

        btn.disabled    = false;
        btn.textContent = 'অর্ডার দিন →';
    });
});
</script>

</body>
</html>
