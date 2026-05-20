@php
/* Prepare a clean JS-safe product payload — no extra queries, activePrices already eager-loaded */
$productsForJs = $products->map(function ($p) {
    $mapPrice = fn($pr) => [
        'id'                 => $pr->id,
        'label'              => $pr->label,
        'quantity_gram'      => (int) $pr->quantity_gram,
        'final_price'        => (float) $pr->final_price,
        'is_manual_override' => (bool) $pr->is_manual_override,
    ];
    /* Product-level prices (no variant) */
    $directPrices = $p->activePrices->filter(fn($pr) => is_null($pr->product_variant_id));
    /* Variant data */
    $variants = $p->activeVariants->map(fn($v) => [
        'id'               => $v->id,
        'name'             => $v->name,
        'retail_prices'    => $v->activePrices->where('sell_type', 'retail')->map($mapPrice)->values()->all(),
        'wholesale_prices' => $v->activePrices->where('sell_type', 'wholesale')->map($mapPrice)->values()->all(),
    ])->values()->all();
    return [
        'id'                => $p->id,
        'name_bn'           => $p->name_bn,
        'name_en'           => $p->name_en,
        'short_description' => $p->short_description,
        'description'       => $p->description,
        'main_image'        => $p->main_image ? asset($p->main_image) : null,
        'gallery_images'    => collect($p->gallery_images ?? [])->map(fn($img) => asset($img))->values()->all(),
        'video_url'         => $p->video_url,
        'video_path'        => ($p->video_path ?? null) ? asset($p->video_path) : null,
        'stock'             => (int) $p->stock,
        'retail_prices'     => $directPrices->where('sell_type', 'retail')->map($mapPrice)->values()->all(),
        'wholesale_prices'  => $directPrices->where('sell_type', 'wholesale')->map($mapPrice)->values()->all(),
        'variants'          => $variants,
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
    </div>
</nav>

{{-- ━━━━━━━━━━━━━━━━  HERO  ━━━━━━━━━━━━━━━━ --}}
<section class="hero-bg py-20 md:py-28 px-5">
    <div class="max-w-4xl mx-auto text-center">

        <div class="flex items-center justify-center gap-4 mb-8">
            <div class="h-px w-20 bg-gradient-to-r from-transparent to-[#c9a227] opacity-70"></div>
            <span class="text-[#c9a227] text-xs tracking-[.35em] uppercase font-semibold">{{ $ws['hero_badge_text'] ?? 'ঈদ স্পেশাল কালেকশন' }}</span>
            <div class="h-px w-20 bg-gradient-to-l from-transparent to-[#c9a227] opacity-70"></div>
        </div>

        <h1 class="font-serif-bn text-cream leading-tight mb-6">
            <span class="block text-5xl md:text-7xl font-bold">{{ $ws['hero_title'] ?? 'খাঁটি মশলার' }}</span>
            <span class="block text-4xl md:text-6xl font-bold text-[#c9a227] mt-1">অপূর্ব স্বাদ</span>
        </h1>
        @if(!empty($ws['hero_subtitle']))
        <p class="text-green-200 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed mb-10">
            {{ $ws['hero_subtitle'] }}
        </p>
        @else
        <p class="text-green-200 text-lg md:text-xl max-w-2xl mx-auto leading-relaxed mb-10">
            প্রকৃতির সেরা উপাদান থেকে তৈরি, ভেজালমুক্ত খাঁটি মশলা —
            আপনার রান্নাকে করে তুলুন অতুলনীয় ও সুস্বাদু।
        </p>
        @endif
        <a href="#products" class="btn-gold inline-flex items-center gap-2 text-[#0f3d22] font-bold text-base px-10 py-3.5 rounded-full shadow-xl">
            {{ $ws['primary_cta_text'] ?? 'পণ্য দেখুন' }}
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

            <div class="flex items-center gap-3 self-start sm:self-auto flex-wrap">
                {{-- Retail / Wholesale tab --}}
                <div class="flex items-center gap-0.5 p-1 bg-white border border-gray-200 rounded-xl shadow-sm">
                    <button id="tab-retail" onclick="setTab('retail')"
                            class="px-4 py-1.5 rounded-lg bg-[#14532d] text-white text-xs font-semibold transition-colors">
                        খুচরা
                    </button>
                    <button id="tab-wholesale" onclick="setTab('wholesale')"
                            class="px-4 py-1.5 rounded-lg text-gray-400 hover:text-gray-600 text-xs font-semibold transition-colors">
                        পাইকারি
                    </button>
                </div>

                {{-- Card / List toggle --}}
                <div class="flex items-center gap-1 p-1 bg-white border border-gray-200 rounded-xl shadow-sm">
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
            @php
                $directRetail = $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'retail');
                $initRetailPrices = $directRetail->isNotEmpty()
                    ? $directRetail
                    : ($product->activeVariants->first()?->activePrices->where('sell_type', 'retail') ?? collect());
            @endphp
            <article data-card-product="{{ $product->id }}" class="product-card bg-white rounded-2xl overflow-hidden shadow border border-green-50 flex flex-col">

                {{-- Image slideshow / placeholder --}}
                @php
                    $cardSlides = collect([$product->main_image])
                        ->merge($product->gallery_images ?? [])
                        ->filter()->values();
                @endphp
                <div class="relative h-52 overflow-hidden flex-shrink-0" data-slideshow="{{ $product->id }}">
                    @if($cardSlides->isNotEmpty())
                        @foreach($cardSlides as $si => $slide)
                        <img src="{{ asset($slide) }}" alt="{{ $product->name_bn }}"
                             class="card-slide absolute inset-0 w-full h-full object-cover transition-opacity duration-500"
                             style="{{ $si > 0 ? 'opacity:0;' : '' }}">
                        @endforeach
                        @if($cardSlides->count() > 1)
                        <div class="absolute bottom-2 left-0 right-0 flex justify-center gap-1 z-10 pointer-events-none">
                            @foreach($cardSlides as $si => $slide)
                            <span class="card-dot w-1.5 h-1.5 rounded-full bg-white transition-opacity"
                                  style="{{ $si === 0 ? 'opacity:.9;' : 'opacity:.4;' }}"></span>
                            @endforeach
                        </div>
                        @endif
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

                    <div id="card-price-wrap-{{ $product->id }}" class="mt-3 flex items-baseline gap-1.5"
                         style="{{ $initRetailPrices->isEmpty() ? 'display:none;' : '' }}">
                        <span id="card-from-{{ $product->id }}" class="text-[#c9a227] font-serif-bn text-2xl font-bold">
                            {{ $initRetailPrices->isNotEmpty() ? '৳' . number_format($initRetailPrices->first()->final_price, 0) : '' }}
                        </span>
                        <span class="text-gray-400 text-xs">থেকে শুরু</span>
                    </div>

                    {{-- Pack price chips --}}
                    <div id="card-chips-{{ $product->id }}" class="mt-3 grid grid-cols-3 gap-1.5">
                        @foreach($initRetailPrices as $price)
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
            @php
                $directRetail = $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'retail');
                $initRetailPrices = $directRetail->isNotEmpty()
                    ? $directRetail
                    : ($product->activeVariants->first()?->activePrices->where('sell_type', 'retail') ?? collect());
            @endphp
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

                        <div id="list-prices-{{ $product->id }}" class="mt-2 flex flex-wrap gap-1">
                            @foreach($initRetailPrices as $price)
                                <span class="text-[11px] bg-green-50 border border-green-100 text-[#14532d] px-2 py-0.5 rounded-full whitespace-nowrap">
                                    {{ $price->label }} · ৳{{ number_format($price->final_price, 0) }}
                                </span>
                            @endforeach
                        </div>
                    </div>

                    {{-- Price + buttons --}}
                    <div class="flex sm:flex-col items-center sm:items-end justify-between gap-2 flex-shrink-0">
                        @if($product->activePrices->isNotEmpty())
                            <div class="text-right">
                                <div id="list-from-{{ $product->id }}" class="text-[#c9a227] font-bold text-xl font-serif-bn">
                                    {{ $initRetailPrices->isNotEmpty() ? '৳' . number_format($initRetailPrices->first()->final_price, 0) : '' }}
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

{{-- ━━━━━━━━━━━━━━━━  FIXED COMBOS  ━━━━━━━━━━━━━━━━ --}}
@if($fixedCombos->isNotEmpty())
<section id="fixed-combos" class="py-16 md:py-20 px-5">
    <div class="max-w-7xl mx-auto">

        <div class="text-center mb-10">
            <div class="flex items-center justify-center gap-4 mb-3">
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
                <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">Ready Packs</span>
                <div class="h-px w-14 bg-[#c9a227] opacity-50"></div>
            </div>
            <h2 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">ফিক্সড কম্বো প্যাক</h2>
            <p class="text-gray-400 text-sm mt-2 max-w-md mx-auto leading-relaxed">
                রেডি-মেড মশলার সেট — এক ক্লিকে অর্ডার করুন।
            </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach($fixedCombos as $combo)
            <div data-combo-type="{{ $combo->sell_type }}"
                 class="bg-white rounded-2xl overflow-hidden shadow border border-green-50 flex flex-col transition-shadow hover:shadow-lg"
                 style="{{ $combo->sell_type === 'wholesale' ? 'display:none;' : '' }}">

                <div class="bg-gradient-to-br from-[#14532d] to-[#1a6b3a] px-5 pt-5 pb-4 relative">
                    @if($combo->badge_text)
                    <span class="absolute top-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#c9a227] text-[#0f3d22]">
                        {{ $combo->badge_text }}
                    </span>
                    @endif
                    <h3 class="font-serif-bn text-[#c9a227] text-xl font-bold leading-snug pr-16">{{ $combo->name }}</h3>
                    @if($combo->short_description)
                    <p class="text-green-300 text-xs mt-1 leading-relaxed">{{ $combo->short_description }}</p>
                    @endif
                    <div class="mt-3 text-[#fef9ee] font-serif-bn text-3xl font-bold">
                        ৳{{ number_format($combo->sell_price, 0) }}
                    </div>
                </div>

                <div class="px-5 py-4 flex-1">
                    <ul class="space-y-1.5">
                        @foreach($combo->items as $item)
                        <li class="flex justify-between items-baseline text-sm">
                            <span class="font-medium text-[#14532d]">{{ $item->product?->name_bn ?? 'পণ্য' }}</span>
                            <span class="text-gray-400 text-xs ml-2 flex-shrink-0">
                                {{ $item->quantity_gram >= 1000 ? ($item->quantity_gram / 1000).' কেজি' : $item->quantity_gram.' গ্রাম' }}
                            </span>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="px-5 pb-5">
                    <button onclick="orderFixedCombo({{ $combo->id }})"
                            class="w-full bg-[#c9a227] hover:bg-[#e2bb45] text-[#0f3d22] font-bold py-3 rounded-xl text-sm shadow transition-colors">
                        এখনই অর্ডার করুন →
                    </button>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<div class="gold-rule"></div>
@endif

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
                    @php
                        $hasVariants = $product->activeVariants->isNotEmpty();
                        $firstVariant = $product->activeVariants->first();
                        $pickerRetailPrices = $hasVariants
                            ? ($firstVariant?->activePrices->where('sell_type', 'retail') ?? collect())
                            : $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'retail');
                        $hasAnyRetail = $pickerRetailPrices->isNotEmpty()
                            || (!$hasVariants && $product->activePrices->filter(fn($pr) => is_null($pr->product_variant_id))->where('sell_type', 'wholesale')->isNotEmpty());
                        $showRow = $product->activePrices->isNotEmpty() || $hasVariants;
                    @endphp
                    @if($showRow)
                    <div id="picker-row-{{ $product->id }}"
                         class="bg-white border border-green-100 rounded-xl p-3 sm:p-4 flex flex-wrap sm:flex-nowrap items-center gap-3 shadow-sm transition-colors duration-300"
                         style="{{ (!$hasVariants && $pickerRetailPrices->isEmpty()) ? 'display:none;' : '' }}">

                        {{-- Initial avatar --}}
                        <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-[#14532d] to-[#1a6b3a] flex items-center justify-center flex-shrink-0 shadow">
                            <span class="text-[#c9a227] font-serif-bn font-bold text-lg">{{ mb_substr($product->name_bn, 0, 1) }}</span>
                        </div>

                        {{-- Name --}}
                        <div class="flex-1 min-w-0 sm:min-w-[130px]">
                            <div class="font-serif-bn text-[#14532d] font-bold text-sm sm:text-base leading-tight">{{ $product->name_bn }}</div>
                            <div class="text-gray-400 text-[11px] uppercase tracking-wider">{{ $product->name_en }}</div>
                        </div>

                        {{-- Variant selector (only for products with variants) --}}
                        @if($hasVariants)
                        <select id="picker-variant-{{ $product->id }}"
                                onchange="pickerVariantChange({{ $product->id }})"
                                class="border border-purple-200 bg-white rounded-xl px-3 py-2 text-sm text-purple-800 font-medium focus:outline-none focus:ring-2 focus:ring-purple-400 w-full sm:w-auto flex-shrink-0">
                            @foreach($product->activeVariants as $variant)
                            <option value="{{ $variant->id }}">{{ $variant->name }}</option>
                            @endforeach
                        </select>
                        @endif

                        {{-- Qty select --}}
                        <select id="picker-qty-{{ $product->id }}"
                                onchange="pickerPriceUpdate({{ $product->id }})"
                                class="border border-green-200 bg-white rounded-xl px-3 py-2 text-sm text-[#14532d] font-medium focus:outline-none focus:ring-2 focus:ring-[#14532d] w-full sm:w-auto flex-shrink-0">
                            @foreach($pickerRetailPrices as $price)
                                <option value="{{ $price->id }}" data-price="{{ $price->final_price }}">{{ $price->label }}</option>
                            @endforeach
                        </select>

                        {{-- Price display --}}
                        <div id="picker-price-{{ $product->id }}"
                             class="text-[#c9a227] font-bold font-serif-bn text-base sm:text-lg min-w-[72px] text-right flex-shrink-0">
                            {{ $pickerRetailPrices->isNotEmpty() ? '৳' . number_format($pickerRetailPrices->first()->final_price, 0) : '' }}
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
                        <p id="min-order-warning" style="display:none"
                           class="text-red-400 text-[11px] text-center mt-2 leading-tight">
                            ন্যূনতম অর্ডার ৳{{ number_format($minOrderAmount, 0) }}। আরো পণ্য যোগ করুন।
                        </p>
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

{{-- ━━━━━━━━━━━━━━━━  REVIEWS  ━━━━━━━━━━━━━━━━ --}}
@if($reviews->isNotEmpty())
<section id="reviews" class="py-16 md:py-20 px-5 bg-[#fef9ee]">
    <div class="max-w-5xl mx-auto">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center gap-4 mb-3">
                <div class="h-px w-14 bg-[#c9a227] opacity-40"></div>
                <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">Customer Reviews</span>
                <div class="h-px w-14 bg-[#c9a227] opacity-40"></div>
            </div>
            <h2 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">গ্রাহকরা কী বলছেন?</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($reviews as $review)
            <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 flex flex-col gap-4 hover:shadow-md transition-shadow">
                <div class="flex items-center gap-1 text-amber-400 text-lg leading-none">
                    @for($s = 1; $s <= 5; $s++)
                        @if($s <= $review->rating)
                            <span>★</span>
                        @else
                            <span class="text-gray-200">★</span>
                        @endif
                    @endfor
                </div>
                <p class="text-gray-700 text-sm leading-relaxed flex-1">"{{ $review->review_text }}"</p>
                <div class="flex items-center gap-3 pt-2 border-t border-gray-50">
                    @if($review->image)
                        <img src="{{ $review->image }}" alt="{{ $review->customer_name }}"
                             class="w-10 h-10 rounded-full object-cover border-2 border-amber-100">
                    @else
                        <div class="w-10 h-10 rounded-full bg-[#14532d] flex items-center justify-center flex-shrink-0">
                            <span class="text-[#c9a227] font-bold text-base">{{ mb_substr($review->customer_name, 0, 1) }}</span>
                        </div>
                    @endif
                    <div>
                        <div class="text-sm font-semibold text-gray-800">{{ $review->customer_name }}</div>
                        @if($review->customer_location)
                            <div class="text-xs text-gray-400">{{ $review->customer_location }}</div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<div class="gold-rule"></div>
@endif

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

{{-- ━━━━━━━━━━━━━━━━  FAQ  ━━━━━━━━━━━━━━━━ --}}
@if($faqs->isNotEmpty())
<section id="faq" class="py-16 md:py-20 px-5">
    <div class="max-w-3xl mx-auto">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center gap-4 mb-3">
                <div class="h-px w-14 bg-[#c9a227] opacity-40"></div>
                <span class="text-[#c9a227] text-xs tracking-[.3em] uppercase font-semibold">FAQ</span>
                <div class="h-px w-14 bg-[#c9a227] opacity-40"></div>
            </div>
            <h2 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">সাধারণ প্রশ্নোত্তর</h2>
        </div>
        <div class="space-y-3" id="faq-list">
            @foreach($faqs as $i => $faq)
            <div class="border border-amber-100 rounded-xl overflow-hidden bg-white shadow-sm">
                <button type="button"
                        onclick="toggleFaq({{ $i }})"
                        class="w-full text-left px-6 py-4 flex items-center justify-between gap-4 hover:bg-amber-50 transition-colors">
                    <span class="text-[#14532d] font-semibold text-sm md:text-base leading-snug">{{ $faq->question }}</span>
                    <svg id="faq-icon-{{ $i }}" class="w-5 h-5 text-[#c9a227] flex-shrink-0 transition-transform duration-200"
                         fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div id="faq-body-{{ $i }}" class="hidden px-6 pb-5">
                    <div class="h-px bg-amber-100 mb-4"></div>
                    <p class="text-gray-600 text-sm leading-relaxed">{{ $faq->answer }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

<div class="gold-rule"></div>
@endif

{{-- ━━━━━━━━━━━━━━━━  CONTACT  ━━━━━━━━━━━━━━━━ --}}
<section id="contact" class="bg-[#fef9ee] py-12 px-5 border-t border-amber-100">
    @php
        $waNum    = $ws['whatsapp_number']   ?? '';
        $msgrUrl  = $ws['messenger_url']     ?? '';
        $fbUrl    = $ws['facebook_page_url'] ?? '';
        $waClean  = preg_replace('/\D/', '', $waNum);
    @endphp
    <div class="max-w-3xl mx-auto text-center">
        <h2 class="font-serif-bn text-[#14532d] text-2xl md:text-3xl font-bold mb-2">অর্ডার করুন এখনই</h2>
        <p class="text-gray-500 text-sm mb-6">ফোনে বা WhatsApp-এ যোগাযোগ করুন — আমরা সর্বদা প্রস্তুত</p>
        <div class="flex flex-wrap items-center justify-center gap-3">
            @if($waNum)
            <a href="https://wa.me/88{{ $waClean }}"
               class="inline-flex items-center gap-2 bg-[#14532d] text-[#fef9ee] font-semibold px-7 py-3.5 rounded-full hover:bg-[#166534] transition-colors shadow-lg text-base">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                {{ $waNum }}
            </a>
            <a href="tel:+88{{ $waClean }}"
               class="inline-flex items-center gap-2 border-2 border-[#14532d] text-[#14532d] font-semibold px-7 py-3.5 rounded-full hover:bg-[#14532d] hover:text-white transition-colors text-base">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                ফোন করুন
            </a>
            @endif
            @if($msgrUrl)
            <a href="{{ $msgrUrl }}"
               class="inline-flex items-center gap-2 bg-blue-600 text-white font-semibold px-7 py-3.5 rounded-full hover:bg-blue-700 transition-colors shadow-lg text-base">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 0C5.373 0 0 4.975 0 11.111c0 3.497 1.745 6.616 4.472 8.652V24l4.086-2.242c1.09.301 2.246.464 3.442.464 6.627 0 12-4.975 12-11.111C24 4.975 18.627 0 12 0zm1.193 14.963l-3.056-3.259-5.963 3.259L10.986 8.4l3.13 3.259L20 8.4l-6.807 6.563z"/></svg>
                Messenger
            </a>
            @endif
            @if($fbUrl)
            <a href="{{ $fbUrl }}"
               class="inline-flex items-center gap-2 bg-[#1877F2] text-white font-semibold px-7 py-3.5 rounded-full hover:bg-blue-800 transition-colors shadow-lg text-base">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                Facebook
            </a>
            @endif
            @if(!$waNum && !$msgrUrl && !$fbUrl)
            <a href="tel:+8801700000000"
               class="inline-flex items-center gap-2 bg-[#14532d] text-[#fef9ee] font-semibold px-8 py-3.5 rounded-full hover:bg-[#166534] transition-colors shadow-lg text-base">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                ০১৭০০-০০০০০০
            </a>
            @endif
        </div>
    </div>
</section>

{{-- ━━━━━━━━━━━━━━━━  FOOTER  ━━━━━━━━━━━━━━━━ --}}
<footer class="bg-[#0f3d22] py-10 px-5">
    <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row justify-between items-center gap-6">
            <div>
                <h3 class="font-serif-bn text-[#c9a227] text-xl font-bold">{{ $ws['site_name'] ?? 'মসলা ঘর' }}</h3>
                <p class="text-green-500 text-xs mt-1">খাঁটি মশলার আস্থার দোকান</p>
            </div>
            <div class="flex gap-6 text-green-500 text-xs">
                <a href="#products" class="hover:text-[#c9a227] transition-colors">পণ্যসমূহ</a>
                <a href="#why-us"   class="hover:text-[#c9a227] transition-colors">আমাদের সম্পর্কে</a>
                <a href="#contact"  class="hover:text-[#c9a227] transition-colors">যোগাযোগ</a>
            </div>
        </div>
        <div class="gold-rule mt-8 mb-6 opacity-20"></div>
        <p class="text-center text-green-700 text-xs">&copy; {{ date('Y') }} {{ $ws['site_name'] ?? 'মসলা ঘর' }} — {{ $ws['footer_text'] ?? 'সমস্ত অধিকার সংরক্ষিত।' }}</p>
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

                {{-- Image slide --}}
                <img id="modal-img" src="" alt=""
                     class="absolute inset-0 w-full h-full object-cover cursor-zoom-in" style="display:none;"
                     onclick="openZoom(modalCurSlide)">

                {{-- Uploaded local video --}}
                <video id="modal-local-video" class="absolute inset-0 w-full h-full object-cover"
                       controls style="display:none;"></video>

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

                {{-- Slideshow prev/next (shown when multiple slides) --}}
                <button id="modal-prev" onclick="modalSlide(-1)"
                        class="absolute left-2 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center text-lg transition-colors"
                        style="display:none;">&#8249;</button>
                <button id="modal-next" onclick="modalSlide(1)"
                        class="absolute right-12 top-1/2 -translate-y-1/2 z-20 w-8 h-8 rounded-full bg-black/40 hover:bg-black/60 text-white flex items-center justify-center text-lg transition-colors"
                        style="display:none;">&#8250;</button>

                {{-- Slide indicator dots --}}
                <div id="modal-dots" class="absolute bottom-2 left-0 right-0 flex justify-center gap-1.5 z-20 pointer-events-none"
                     style="display:none;"></div>

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

                {{-- Variant selector (shown only for products with variants, populated by JS) --}}
                <div id="modal-variant-wrap" style="display:none;" class="mt-4">
                    <h4 class="text-[#14532d] text-sm font-semibold mb-2 flex items-center gap-2">
                        ভ্যারিয়েন্ট বেছে নিন
                        <span class="flex-1 h-px bg-green-100 inline-block"></span>
                    </h4>
                    <select id="modal-variant-sel" onchange="onModalVariantChange(this.value)"
                            class="w-full border border-purple-200 bg-white rounded-xl px-4 py-2.5 text-sm text-purple-800 font-medium focus:outline-none focus:ring-2 focus:ring-purple-400">
                    </select>
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
                    <button onclick="addToComboFromModal()"
                            class="flex-1 border-2 border-[#c9a227] text-[#c9a227] hover:bg-[#c9a227] hover:text-[#0f3d22] py-3 rounded-xl text-sm font-bold transition-colors">
                        + কম্বোতে যোগ করুন
                    </button>
                    <button onclick="orderSingleProduct()"
                            class="flex-1 bg-[#14532d] hover:bg-[#166534] text-[#fef9ee] py-3 rounded-xl text-sm font-bold transition-colors shadow">
                        এখনই অর্ডার করুন →
                    </button>
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
                <div class="mt-2.5 border-t border-green-200 pt-2 space-y-1">
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>প্যাকেজিং চার্জ</span>
                        <span>৳{{ number_format($packagingCost, 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-gray-500">
                        <span>ডেলিভারি চার্জ</span>
                        <span id="order-delivery-display" class="text-gray-400 italic">এলাকা বেছে নিন</span>
                    </div>
                </div>
                <div class="flex justify-between items-center font-bold text-[#14532d] pt-2.5 mt-1 border-t border-green-200">
                    <span class="text-sm">সর্বমোট</span>
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
                <input type="hidden" name="order_type" id="order-type-input" value="custom">
                <input type="hidden" name="combo_id" id="f-combo_id">
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

                    {{-- BD Address cascade --}}
                    <div>
                        <div class="text-[#14532d] text-[10px] font-bold uppercase tracking-widest mb-3 flex items-center gap-2">
                            <span>ঠিকানা</span>
                            <span class="flex-1 h-px bg-green-100 inline-block"></span>
                        </div>

                        {{-- Division --}}
                        <div class="mb-3" id="division-wrap">
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">বিভাগ <span class="text-red-400">*</span></label>
                            <button type="button" id="division-btn" onclick="toggleAddrSection('division')"
                                    class="w-full flex justify-between items-center border border-green-200 rounded-xl px-4 py-2.5 bg-white text-sm hover:border-[#14532d] transition-colors">
                                <span id="division-display" class="text-gray-400">বিভাগ বেছে নিন</span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="division-section" class="hidden mt-2">
                                <input type="text" id="division-search" placeholder="বিভাগ খুঁজুন..."
                                       oninput="searchAddr('division', this.value)"
                                       class="w-full border border-green-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#14532d] bg-white mb-1">
                                <div id="division-list" class="border border-green-100 rounded-xl max-h-40 overflow-y-auto bg-white"></div>
                            </div>
                            <p id="err-bd_division_id" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        {{-- District --}}
                        <div class="mb-3" id="district-wrap" style="display:none">
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">জেলা <span class="text-red-400">*</span></label>
                            <button type="button" id="district-btn" onclick="toggleAddrSection('district')"
                                    class="w-full flex justify-between items-center border border-green-200 rounded-xl px-4 py-2.5 bg-white text-sm hover:border-[#14532d] transition-colors">
                                <span id="district-display" class="text-gray-400">জেলা বেছে নিন</span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="district-section" class="hidden mt-2">
                                <input type="text" id="district-search" placeholder="জেলা খুঁজুন..."
                                       oninput="searchAddr('district', this.value)"
                                       class="w-full border border-green-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#14532d] bg-white mb-1">
                                <div id="district-list" class="border border-green-100 rounded-xl max-h-40 overflow-y-auto bg-white"></div>
                            </div>
                            <p id="err-bd_district_id" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        {{-- Upazila --}}
                        <div class="mb-3" id="upazila-wrap" style="display:none">
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">উপজেলা <span class="text-red-400">*</span></label>
                            <button type="button" id="upazila-btn" onclick="toggleAddrSection('upazila')"
                                    class="w-full flex justify-between items-center border border-green-200 rounded-xl px-4 py-2.5 bg-white text-sm hover:border-[#14532d] transition-colors">
                                <span id="upazila-display" class="text-gray-400">উপজেলা বেছে নিন</span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="upazila-section" class="hidden mt-2">
                                <input type="text" id="upazila-search" placeholder="উপজেলা খুঁজুন..."
                                       oninput="searchAddr('upazila', this.value)"
                                       class="w-full border border-green-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#14532d] bg-white mb-1">
                                <div id="upazila-list" class="border border-green-100 rounded-xl max-h-40 overflow-y-auto bg-white"></div>
                            </div>
                            <p id="err-bd_upazila_id" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        {{-- Union --}}
                        <div class="mb-0" id="union-wrap" style="display:none">
                            <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">ইউনিয়ন / এলাকা</label>
                            <button type="button" id="union-btn" onclick="toggleAddrSection('union')"
                                    class="w-full flex justify-between items-center border border-green-200 rounded-xl px-4 py-2.5 bg-white text-sm hover:border-[#14532d] transition-colors">
                                <span id="union-display" class="text-gray-400">ইউনিয়ন / এলাকা বেছে নিন</span>
                                <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div id="union-section" class="hidden mt-2">
                                <input type="text" id="union-search" placeholder="ইউনিয়ন / এলাকা খুঁজুন..."
                                       oninput="searchAddr('union', this.value)"
                                       class="w-full border border-green-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-[#14532d] bg-white mb-1">
                                <div id="union-list" class="border border-green-100 rounded-xl max-h-40 overflow-y-auto bg-white"></div>
                            </div>
                            <p id="err-bd_union_id" class="text-red-500 text-xs mt-1 hidden"></p>
                        </div>

                        {{-- Hidden address IDs --}}
                        <input type="hidden" name="bd_division_id" id="f-bd_division_id">
                        <input type="hidden" name="bd_district_id" id="f-bd_district_id">
                        <input type="hidden" name="bd_upazila_id" id="f-bd_upazila_id">
                        <input type="hidden" name="bd_union_id" id="f-bd_union_id">
                    </div>

                    {{-- full_address --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                            বাড়ি / ফ্ল্যাট / রাস্তা <span class="text-red-400">*</span>
                        </label>
                        <textarea name="full_address" id="f-full_address" rows="2"
                                  placeholder="বাড়ি/ফ্ল্যাট নম্বর, রাস্তা, মহল্লা..."
                                  class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white resize-none"></textarea>
                        <p id="err-full_address" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    {{-- hidden fields populated by JS --}}
                    <input type="hidden" name="delivery_zone_id"     id="f-delivery_zone_id">
                    <input type="hidden" name="delivery_location_id" id="f-delivery_location_id">

                    {{-- Zone selection --}}
                    <div>
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-2">
                            ডেলিভারি জোন <span class="text-red-400">*</span>
                        </label>
                        @if($activeZones->isEmpty())
                            <p class="text-gray-400 text-sm">কোনো ডেলিভারি জোন পাওয়া যায়নি। অ্যাডমিনকে জানান।</p>
                        @else
                        <div class="grid grid-cols-2 gap-3">
                            @foreach($activeZones as $zone)
                            <label for="zone-radio-{{ $zone->id }}" class="block cursor-pointer">
                                <input type="radio" name="_zone_radio" id="zone-radio-{{ $zone->id }}"
                                       value="{{ $zone->id }}" class="sr-only"
                                       onchange="onZoneSelect({{ $zone->id }})">
                                <div id="zone-display-{{ $zone->id }}"
                                     class="border-2 border-green-200 rounded-xl px-3 py-3 text-center transition-colors bg-white hover:border-[#14532d]">
                                    <div class="text-sm font-bold text-[#14532d]">{{ $zone->zone_name }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">৳{{ number_format($zone->delivery_charge, 0) }}</div>
                                </div>
                            </label>
                            @endforeach
                        </div>
                        @endif
                        <p id="err-delivery_zone_id" class="text-red-500 text-xs mt-1 hidden"></p>
                    </div>

                    {{-- Location selection (appears after zone is chosen) --}}
                    <div id="location-section" style="display:none">
                        <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-2">
                            এলাকা বেছে নিন <span class="text-red-400">*</span>
                        </label>
                        <input type="text" id="location-search"
                               placeholder="এলাকা খুঁজুন..."
                               oninput="filterLocations(this.value)"
                               class="w-full border border-green-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] bg-white mb-2">
                        <div id="location-list" class="space-y-2 max-h-52 overflow-y-auto pr-1"></div>
                        <p id="err-delivery_location_id" class="text-red-500 text-xs mt-1 hidden"></p>
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
                            <div>
                                <label class="block text-[#14532d] text-xs font-semibold uppercase tracking-wider mb-1.5">
                                    পেমেন্ট স্ক্রিনশট
                                    <span class="text-gray-400 text-xs font-normal normal-case">(ঐচ্ছিক)</span>
                                </label>
                                <label for="f-payment_screenshot"
                                       class="flex items-center gap-3 w-full border-2 border-dashed border-green-200 rounded-xl px-4 py-3 cursor-pointer bg-white hover:border-[#14532d] transition-colors">
                                    <span class="text-2xl">📷</span>
                                    <span id="screenshot-label-text" class="text-sm text-gray-500">
                                        ছবি বেছে নিন (JPG, PNG, WebP • সর্বোচ্চ ২ MB)
                                    </span>
                                </label>
                                <input type="file" name="payment_screenshot" id="f-payment_screenshot"
                                       accept="image/jpeg,image/png,image/webp"
                                       class="sr-only"
                                       onchange="onScreenshotChange(this)">
                                <p id="err-payment_screenshot" class="text-red-500 text-xs mt-1 hidden"></p>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Marketing consent --}}
                <div class="px-6 pb-4">
                    <label class="flex items-start gap-2.5 cursor-pointer select-none">
                        <input type="checkbox" name="accepts_marketing" value="1"
                               id="f-accepts_marketing"
                               class="mt-0.5 flex-shrink-0 rounded border-green-300 text-[#14532d] focus:ring-[#14532d]">
                        <span class="text-gray-500 text-xs leading-relaxed">
                            আমি মসলা ঘর থেকে নতুন অফার ও আপডেট পেতে চাই।
                        </span>
                    </label>
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

{{-- ━━━━━━━━━━━━━━━━  IMAGE ZOOM LIGHTBOX  ━━━━━━━━━━━━━━━━ --}}
<div id="zoom-overlay" onclick="closeZoom()"
     class="fixed inset-0 z-[300] bg-black/95 flex items-center justify-center"
     style="display:none;">

    <button id="zoom-close" onclick="event.stopPropagation(); closeZoom()"
            class="absolute top-4 right-4 z-10 w-10 h-10 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center text-2xl leading-none transition-colors"
            aria-label="close">&times;</button>

    <div id="zoom-counter"
         class="absolute top-4 left-1/2 -translate-x-1/2 z-10 bg-black/40 text-white/90 text-sm font-medium px-3 py-1 rounded-full select-none"
         style="display:none;"></div>

    <button id="zoom-prev" onclick="event.stopPropagation(); zoomNav(-1)"
            class="absolute left-3 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center text-3xl leading-none transition-colors"
            style="display:none;">&#8249;</button>

    <button id="zoom-next" onclick="event.stopPropagation(); zoomNav(1)"
            class="absolute right-3 top-1/2 -translate-y-1/2 z-10 w-11 h-11 rounded-full bg-white/10 hover:bg-white/25 text-white flex items-center justify-center text-3xl leading-none transition-colors"
            style="display:none;">&#8250;</button>

    <img id="zoom-img" src="" alt="" onclick="event.stopPropagation()"
         class="object-contain select-none"
         style="max-height:90vh; max-width:90vw;">
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
const MIN_ORDER_AMOUNT  = {{ (int) $minOrderAmount }};
const PAYMENT_SETTINGS  = {
    bkash_number:        @json($paymentSettings->bkash_number),
    rocket_number:       @json($paymentSettings->rocket_number),
    nagad_number:        @json($paymentSettings->nagad_number),
    payment_instruction: @json($paymentSettings->payment_instruction),
    enabled_methods:     @json($paymentSettings->enabledMethods()),
};
const DELIVERY_ZONES = @json($zonesForJs);
const FIXED_COMBOS   = @json($fixedCombosForJs);
const BD_DIVISIONS   = @json($bdDivisions);
const BD_DISTRICTS   = @json($bdDistricts);
const BD_UPAZILAS    = @json($bdUpazilas);

let currentId      = null;
let fixedComboData = null;
let modalSlides    = [];
let modalCurSlide        = 0;
let activeTab            = 'retail'; // 'retail' | 'wholesale'
let modalCurrentVariantId = null;

// ── Tab helpers ───────────────────────────────────────────────────────────
function activeTabPrices(p) {
    const direct = activeTab === 'retail' ? (p.retail_prices || []) : (p.wholesale_prices || []);
    if (direct.length > 0) return direct;
    // Fall back to first variant's prices for display purposes
    if (p.variants && p.variants.length > 0) {
        const v = p.variants[0];
        return activeTab === 'retail' ? (v.retail_prices || []) : (v.wholesale_prices || []);
    }
    return [];
}

function getVariantPrices(p, variantId) {
    const v = (p.variants || []).find(x => x.id === variantId);
    if (!v) return activeTabPrices(p);
    return activeTab === 'retail' ? (v.retail_prices || []) : (v.wholesale_prices || []);
}

function pickerVariantChange(productId) {
    const p = PRODUCTS[productId];
    if (!p || !p.variants || p.variants.length === 0) return;
    const varSel = document.getElementById('picker-variant-' + productId);
    if (!varSel) return;
    const variantId = parseInt(varSel.value, 10);
    const prices = getVariantPrices(p, variantId);
    const sel = document.getElementById('picker-qty-' + productId);
    if (sel) {
        sel.innerHTML = prices.map(pr =>
            '<option value="' + pr.id + '" data-price="' + pr.final_price + '">' + pr.label + '</option>'
        ).join('');
        pickerPriceUpdate(productId);
    }
}

function onModalVariantChange(variantIdStr) {
    modalCurrentVariantId = parseInt(variantIdStr, 10);
    const p = PRODUCTS[currentId];
    if (!p) return;
    rebuildModalPrices(getVariantPrices(p, modalCurrentVariantId));
}

function rebuildModalPrices(prices) {
    const grid = document.getElementById('modal-prices-grid');
    if (grid) {
        grid.innerHTML = prices.map(pr =>
            '<div id="mchip-' + pr.id + '" class="p-chip p-2 text-center" onclick="selectChip(' + pr.id + ')">' +
            '<div class="chip-lbl text-gray-400 text-[10px] leading-tight">' + pr.label + '</div>' +
            '<div class="chip-val text-[#14532d] text-sm font-bold leading-tight mt-0.5">৳' + fmt(pr.final_price) + '</div>' +
            (pr.is_manual_override ? '<div style="color:#c9a227;font-size:9px;">★</div>' : '') +
            '</div>'
        ).join('');
    }
    const sel = document.getElementById('modal-qty-sel');
    if (sel) {
        sel.innerHTML = '<option value="">— পরিমাণ বেছে নিন —</option>'
            + prices.map(pr =>
                '<option value="' + pr.id + '">৳' + fmt(pr.final_price) + ' — ' + pr.label + '</option>'
            ).join('');
    }
    const sp = document.getElementById('modal-sel-price');
    if (sp) sp.textContent = '——';
}

const TAB_ON      = 'px-4 py-1.5 rounded-lg bg-[#14532d] text-white text-xs font-semibold transition-colors';
const TAB_WS_ON   = 'px-4 py-1.5 rounded-lg bg-orange-600 text-white text-xs font-semibold transition-colors';
const TAB_OFF     = 'px-4 py-1.5 rounded-lg text-gray-400 hover:text-gray-600 text-xs font-semibold transition-colors';

function setTab(tab) {
    activeTab = tab;
    const rb = document.getElementById('tab-retail');
    const wb = document.getElementById('tab-wholesale');
    if (rb) rb.className = tab === 'retail' ? TAB_ON  : TAB_OFF;
    if (wb) wb.className = tab === 'wholesale' ? TAB_WS_ON : TAB_OFF;
    // Clear combo when switching tab
    comboItems = [];
    renderCombo();
    refreshCardsForTab();
    refreshListViewForTab();
    refreshPickerForTab();
    refreshCombosForTab();
    try { localStorage.setItem('mstab', tab); } catch(e) {}
}

function refreshCardsForTab() {
    Object.values(PRODUCTS).forEach(function(p) {
        const prices = activeTabPrices(p);
        const fp   = document.getElementById('card-from-' + p.id);
        const wrap = document.getElementById('card-price-wrap-' + p.id);
        const cc   = document.getElementById('card-chips-' + p.id);
        if (wrap) wrap.style.display = prices.length ? '' : 'none';
        if (fp)   fp.textContent = prices.length ? '৳' + fmt(prices[0].final_price) : '';
        if (cc)   cc.innerHTML = prices.map(function(pr) {
            return '<div class="p-chip p-1.5 text-center">' +
                '<div class="chip-lbl text-gray-400 text-[10px] leading-tight">' + pr.label + '</div>' +
                '<div class="chip-val text-[#14532d] text-[13px] font-semibold leading-tight mt-0.5">৳' + fmt(pr.final_price) + '</div>' +
                (pr.is_manual_override ? '<div style="color:#c9a227;font-size:9px;">★</div>' : '') +
                '</div>';
        }).join('');
    });
}

function refreshListViewForTab() {
    Object.values(PRODUCTS).forEach(function(p) {
        const prices = activeTabPrices(p);
        const pc = document.getElementById('list-prices-' + p.id);
        const lf = document.getElementById('list-from-' + p.id);
        if (pc) pc.innerHTML = prices.map(function(pr) {
            return '<span class="text-[11px] bg-green-50 border border-green-100 text-[#14532d] px-2 py-0.5 rounded-full whitespace-nowrap">' + pr.label + ' · ৳' + fmt(pr.final_price) + '</span>';
        }).join('');
        if (lf) lf.textContent = prices.length ? '৳' + fmt(prices[0].final_price) : '';
    });
}

function refreshPickerForTab() {
    Object.values(PRODUCTS).forEach(function(p) {
        const row = document.getElementById('picker-row-' + p.id);
        if (!row) return;

        if (p.variants && p.variants.length > 0) {
            // Variant product: show if any variant has prices for this tab
            const anyPrices = p.variants.some(function(v) {
                return (activeTab === 'retail' ? v.retail_prices : v.wholesale_prices).length > 0;
            });
            if (!anyPrices) { row.style.display = 'none'; return; }
            row.style.display = '';
            pickerVariantChange(p.id);
        } else {
            const prices = activeTabPrices(p);
            if (prices.length === 0) { row.style.display = 'none'; return; }
            row.style.display = '';
            const sel = document.getElementById('picker-qty-' + p.id);
            if (sel) {
                sel.innerHTML = prices.map(function(pr) {
                    return '<option value="' + pr.id + '" data-price="' + pr.final_price + '">' + pr.label + '</option>';
                }).join('');
                pickerPriceUpdate(p.id);
            }
        }
    });
}

function refreshCombosForTab() {
    const cards = document.querySelectorAll('[data-combo-type]');
    cards.forEach(function(card) {
        card.style.display = card.dataset.comboType === activeTab ? '' : 'none';
    });
    const section = document.getElementById('fixed-combos');
    if (section) {
        const anyVisible = Array.from(cards).some(function(c) { return c.dataset.comboType === activeTab; });
        section.style.display = anyVisible ? '' : 'none';
    }
}

// Restore saved tab
try { const saved = localStorage.getItem('mstab'); if (saved === 'wholesale') setTab('wholesale'); } catch(e) {}

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
    closeZoom();
    document.getElementById('modal-overlay').style.display = 'none';
    document.getElementById('modal-wrapper').style.display = 'none';
    document.getElementById('modal-video').src = '';
    const lv = document.getElementById('modal-local-video');
    if (lv) { lv.pause(); lv.src = ''; }
    document.body.style.overflow = '';
    currentId = null;
    modalSlides = [];
}

// ── Populate modal ────────────────────────────────────────────────────────
function fillModal(p) {
    // Names
    document.getElementById('modal-name-bn').textContent = p.name_bn;
    document.getElementById('modal-name-en').textContent = p.name_en;
    document.getElementById('modal-ph-bn').textContent   = p.name_bn;
    document.getElementById('modal-ph-en').textContent   = p.name_en;

    // Media elements
    const imgEl      = document.getElementById('modal-img');
    const localVidEl = document.getElementById('modal-local-video');
    const ytEl       = document.getElementById('modal-video');
    const phEl       = document.getElementById('modal-ph');
    const prevBtn    = document.getElementById('modal-prev');
    const nextBtn    = document.getElementById('modal-next');
    const dotsEl     = document.getElementById('modal-dots');

    [imgEl, localVidEl, ytEl, phEl].forEach(el => { if (el) el.style.display = 'none'; });
    if (prevBtn) prevBtn.style.display = 'none';
    if (nextBtn) nextBtn.style.display = 'none';
    if (dotsEl)  dotsEl.style.display  = 'none';
    if (localVidEl) { localVidEl.pause(); localVidEl.src = ''; }
    ytEl.src = '';

    const ytId = ytExtract(p.video_url);
    if (ytId) {
        // YouTube takes priority — show iframe, no slideshow
        ytEl.src = 'https://www.youtube.com/embed/' + ytId + '?rel=0';
        ytEl.style.display = 'block';
        modalSlides = [];
    } else {
        // Build slides: images first, then local video if any
        modalSlides = [];
        if (p.main_image) modalSlides.push({ type: 'image', src: p.main_image });
        (p.gallery_images || []).forEach(img => modalSlides.push({ type: 'image', src: img }));
        if (p.video_path)  modalSlides.push({ type: 'video', src: p.video_path });

        if (modalSlides.length === 0) {
            phEl.style.display = 'flex';
        } else {
            modalCurSlide = 0;
            modalShowSlide(0);

            if (modalSlides.length > 1) {
                if (prevBtn) prevBtn.style.display = 'flex';
                if (nextBtn) nextBtn.style.display = 'flex';
                if (dotsEl) {
                    dotsEl.style.display = 'flex';
                    dotsEl.innerHTML = modalSlides.map((_, i) =>
                        `<span class="modal-dot w-2 h-2 rounded-full bg-white transition-opacity" style="opacity:${i === 0 ? '.9' : '.4'};"></span>`
                    ).join('');
                }
            }
        }
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

    // Variant selector
    const variantWrap = document.getElementById('modal-variant-wrap');
    const variantSel  = document.getElementById('modal-variant-sel');
    if (p.variants && p.variants.length > 0) {
        if (variantWrap) variantWrap.style.display = 'block';
        if (variantSel) {
            variantSel.innerHTML = p.variants.map(v => `<option value="${v.id}">${v.name}</option>`).join('');
            modalCurrentVariantId = p.variants[0].id;
        }
    } else {
        if (variantWrap) variantWrap.style.display = 'none';
        modalCurrentVariantId = null;
    }

    // Prices grid
    const prices = modalCurrentVariantId ? getVariantPrices(p, modalCurrentVariantId) : activeTabPrices(p);
    rebuildModalPrices(prices);
}

// ── Modal slideshow controls ───────────────────────────────────────────────
function modalShowSlide(index) {
    if (!modalSlides.length) return;
    modalCurSlide = ((index % modalSlides.length) + modalSlides.length) % modalSlides.length;
    const slide      = modalSlides[modalCurSlide];
    const imgEl      = document.getElementById('modal-img');
    const localVidEl = document.getElementById('modal-local-video');

    if (imgEl) imgEl.style.display = 'none';
    if (localVidEl) { localVidEl.style.display = 'none'; localVidEl.pause(); }

    if (slide.type === 'image') {
        if (imgEl) { imgEl.src = slide.src; imgEl.style.display = 'block'; }
    } else if (slide.type === 'video') {
        if (localVidEl) { localVidEl.src = slide.src; localVidEl.style.display = 'block'; }
    }

    document.querySelectorAll('.modal-dot').forEach((dot, i) => {
        dot.style.opacity = (i === modalCurSlide) ? '.9' : '.4';
    });
}

function modalSlide(dir) {
    modalShowSlide(modalCurSlide + dir);
}

// ── Price chip selection ──────────────────────────────────────────────────
function selectChip(priceId) {
    const p = PRODUCTS[currentId];
    if (!p) return;
    const prices = modalCurrentVariantId ? getVariantPrices(p, modalCurrentVariantId) : activeTabPrices(p);
    const price = prices.find(x => x.id === priceId);
    if (!price) return;

    // Reset all chips
    prices.forEach(x => {
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
document.addEventListener('keydown', e => {
    const zoomOpen = document.getElementById('zoom-overlay')?.style.display !== 'none';
    if (zoomOpen) {
        if (e.key === 'Escape')     { closeZoom(); return; }
        if (e.key === 'ArrowLeft')  { zoomNav(-1); return; }
        if (e.key === 'ArrowRight') { zoomNav(1);  return; }
        return;
    }
    if (e.key === 'Escape') { closeModal(); closeOrderForm(); }
});

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

    let price, variantId = null, variantName = null;
    if (p.variants && p.variants.length > 0) {
        const varSel = document.getElementById('picker-variant-' + productId);
        if (varSel) {
            variantId = parseInt(varSel.value, 10);
            const v = p.variants.find(x => x.id === variantId);
            if (v) { variantName = v.name; price = getVariantPrices(p, variantId).find(x => x.id === priceId); }
        }
    } else {
        price = activeTabPrices(p).find(x => x.id === priceId);
    }
    if (!price) return;

    // Duplicate → flash existing item in summary
    const dup = comboItems.find(x => x.productId === productId && x.priceId === priceId && x.variantId === variantId);
    if (dup) { flashComboItem(dup.uid); return; }

    comboUid++;
    comboItems.push({ uid: comboUid, productId, priceId, variantId, variantName, sellType: activeTab, quantity_gram: price.quantity_gram, nameBn: p.name_bn, label: price.label, price: price.final_price });

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
    const p = PRODUCTS[item.productId];
    let itemPrices;
    if (item.variantId && p && p.variants) {
        const v = p.variants.find(x => x.id === item.variantId);
        if (v) itemPrices = item.sellType === 'retail' ? (v.retail_prices || []) : (v.wholesale_prices || []);
    }
    if (!itemPrices) itemPrices = p ? (item.sellType === 'retail' ? (p.retail_prices || []) : (p.wholesale_prices || [])) : [];
    const price = itemPrices.find(x => x.id === parseInt(newPriceIdStr, 10));
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
        const p = PRODUCTS[item.productId];
        let itemPrices;
        if (item.variantId && p && p.variants) {
            const v = p.variants.find(x => x.id === item.variantId);
            if (v) itemPrices = item.sellType === 'retail' ? (v.retail_prices || []) : (v.wholesale_prices || []);
        }
        if (!itemPrices) itemPrices = p ? (item.sellType === 'retail' ? (p.retail_prices || []) : (p.wholesale_prices || [])) : [];
        const opts = itemPrices.map(pr =>
            `<option value="${pr.id}"${pr.id === item.priceId ? ' selected' : ''}>${pr.label} · ৳${fmt(pr.final_price)}</option>`
        ).join('');
        const variantLine = item.variantName ? `<div class="text-green-400 text-[10px] leading-none mt-0.5">${item.variantName}</div>` : '';
        return `
        <div id="citem-${item.uid}" class="flex items-center gap-2 py-2.5 border-b border-green-700/30 last:border-0 transition-colors">
            <div class="flex-1 min-w-0">
                <div class="text-[#fef9ee] font-serif-bn text-sm font-semibold leading-tight truncate">${item.nameBn}</div>
                ${variantLine}
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
    const sub    = comboItems.reduce((s, x) => s + x.price, 0);
    const grand  = sub + PACKAGING_COST;
    const minOk  = grand >= MIN_ORDER_AMOUNT;
    subEl.textContent   = '৳' + fmt(sub);
    totalEl.textContent = '৳' + fmt(n > 0 ? grand : PACKAGING_COST);

    // Min order warning
    const minWarn = document.getElementById('min-order-warning');
    if (minWarn) minWarn.style.display = (n > 0 && !minOk) ? 'block' : 'none';

    // Order button active/disabled
    if (orderBtn) {
        if (n > 0 && minOk) {
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

// ── Single Product Order ──────────────────────────────────────────────────
function orderSingleProduct() {
    const p = PRODUCTS[currentId];
    if (!p) return;
    const sel     = document.getElementById('modal-qty-sel');
    const priceId = parseInt(sel ? sel.value : 0, 10);
    if (!priceId) {
        if (sel) { sel.style.borderColor = '#ef4444'; setTimeout(() => { sel.style.borderColor = ''; }, 1500); }
        return;
    }

    let price, variantId = null, variantName = null;
    if (p.variants && p.variants.length > 0 && modalCurrentVariantId) {
        variantId = modalCurrentVariantId;
        const v = p.variants.find(x => x.id === variantId);
        if (v) { variantName = v.name; price = getVariantPrices(p, variantId).find(x => x.id === priceId); }
    } else {
        price = activeTabPrices(p).find(x => x.id === priceId);
    }
    if (!price) return;

    comboUid++;
    comboItems = [{ uid: comboUid, productId: currentId, priceId, variantId, variantName, sellType: activeTab, quantity_gram: price.quantity_gram, nameBn: p.name_bn, label: price.label, price: price.final_price }];

    renderCombo();
    closeModal();
    openOrderForm();
}

// ── Add to Combo from Modal ───────────────────────────────────────────────
function addToComboFromModal() {
    const p = PRODUCTS[currentId];
    if (!p) return;
    const sel     = document.getElementById('modal-qty-sel');
    const priceId = parseInt(sel ? sel.value : 0, 10);
    if (!priceId) {
        if (sel) { sel.style.borderColor = '#ef4444'; setTimeout(() => { sel.style.borderColor = ''; }, 1500); }
        return;
    }

    let price, variantId = null, variantName = null;
    if (p.variants && p.variants.length > 0 && modalCurrentVariantId) {
        variantId = modalCurrentVariantId;
        const v = p.variants.find(x => x.id === variantId);
        if (v) { variantName = v.name; price = getVariantPrices(p, variantId).find(x => x.id === priceId); }
    } else {
        price = activeTabPrices(p).find(x => x.id === priceId);
    }
    if (!price) return;

    const dup = comboItems.find(x => x.productId === currentId && x.priceId === priceId && x.variantId === variantId);
    if (dup) { closeModal(); flashComboItem(dup.uid); return; }

    comboUid++;
    comboItems.push({ uid: comboUid, productId: currentId, priceId, variantId, variantName, sellType: activeTab, quantity_gram: price.quantity_gram, nameBn: p.name_bn, label: price.label, price: price.final_price });
    renderCombo();
    closeModal();

    const sec = document.getElementById('combo-builder');
    if (sec) sec.scrollIntoView({ behavior: 'smooth' });
}

// ── Fixed Combo Order ─────────────────────────────────────────────────────
function orderFixedCombo(comboId) {
    const combo = FIXED_COMBOS.find(c => c.id === comboId);
    if (!combo) return;
    fixedComboData = combo;
    comboItems = [];
    renderCombo();
    openOrderForm();
}

// ── Order Form ────────────────────────────────────────────────────────────
function openOrderForm() {
    if (!fixedComboData && comboItems.length === 0) return;

    const sub   = getOrderSubtotal();
    const grand = sub + PACKAGING_COST;

    if (!fixedComboData && grand < MIN_ORDER_AMOUNT) {
        const minWarn = document.getElementById('min-order-warning');
        if (minWarn) minWarn.style.display = 'block';
        return;
    }

    // Set order type & combo_id
    const orderTypeInput = document.getElementById('order-type-input');
    const comboIdInput   = document.getElementById('f-combo_id');
    if (fixedComboData) {
        if (orderTypeInput) orderTypeInput.value = 'fixed_combo';
        if (comboIdInput)   comboIdInput.value   = fixedComboData.id;
    } else {
        if (orderTypeInput) orderTypeInput.value = comboItems.length === 1 ? 'single_product' : 'custom';
        if (comboIdInput)   comboIdInput.value   = '';
    }

    // Build read-only combo preview
    const preview = document.getElementById('order-items-preview');
    if (preview) {
        if (fixedComboData) {
            const nameRow = `<div class="text-[#14532d] text-xs font-bold uppercase tracking-wider mb-1">${fixedComboData.name}</div>`;
            const itemRows = fixedComboData.items.map(item =>
                `<div class="flex justify-between items-baseline gap-2">
                    <span class="text-gray-700 font-serif-bn font-medium truncate">${item.product_name}
                        <span class="text-gray-400 text-[11px] font-sans">(${item.label})</span>
                    </span>
                    <span class="text-[#c9a227] font-bold font-serif-bn flex-shrink-0">৳${fmt(item.unit_price)}</span>
                </div>`
            ).join('');
            preview.innerHTML = nameRow + itemRows;
        } else {
            preview.innerHTML = comboItems.map(item =>
                `<div class="flex justify-between items-baseline gap-2">
                    <span class="text-gray-700 font-serif-bn font-medium truncate">${item.nameBn}
                        <span class="text-gray-400 text-[11px] font-sans">(${item.label})</span>
                    </span>
                    <span class="text-[#c9a227] font-bold font-serif-bn flex-shrink-0">৳${fmt(item.price)}</span>
                </div>`
            ).join('');
        }
    }

    // Reset BD address
    resetBdAddress();

    // Reset zone/location selection
    selectedZoneId = null;
    selectedLocationId = null;
    currentZoneLocations = [];

    document.querySelectorAll('input[name="_zone_radio"]').forEach(r => r.checked = false);
    DELIVERY_ZONES.forEach(z => {
        const d = document.getElementById('zone-display-' + z.id);
        if (d) d.className = ZONE_CARD_DEFAULT;
    });

    const zInput = document.getElementById('f-delivery_zone_id');
    if (zInput) zInput.value = '';
    const lInput = document.getElementById('f-delivery_location_id');
    if (lInput) lInput.value = '';

    const locSec = document.getElementById('location-section');
    if (locSec) { locSec.style.display = 'none'; }
    const locList = document.getElementById('location-list');
    if (locList) locList.innerHTML = '';
    const locSearch = document.getElementById('location-search');
    if (locSearch) locSearch.value = '';

    const delivEl = document.getElementById('order-delivery-display');
    if (delivEl) { delivEl.textContent = 'এলাকা বেছে নিন'; delivEl.className = 'text-gray-400 italic'; }

    // Show subtotal + packaging; delivery will be added when location is selected
    const tel = document.getElementById('order-total-display');
    if (tel) tel.textContent = '৳' + fmt(getOrderSubtotal() + PACKAGING_COST);

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
    fixedComboData = null;
    const comboIdInput = document.getElementById('f-combo_id');
    if (comboIdInput) comboIdInput.value = '';
    resetBdAddress();
}

function clearOrderErrors() {
    ['full_name','mobile_number','alternative_number',
     'bd_division_id','bd_district_id','bd_upazila_id','bd_union_id',
     'full_address',
     'delivery_zone_id','delivery_location_id',
     'items','payment_method','sender_number','transaction_id','paid_amount','payment_screenshot'].forEach(f => {
        const el = document.getElementById('err-' + f);
        if (el) { el.textContent = ''; el.classList.add('hidden'); }
    });
    const ge = document.getElementById('order-general-error');
    if (ge) ge.classList.add('hidden');
}

// ── Delivery Zone + Location ──────────────────────────────────────────────
let selectedZoneId       = null;
let selectedLocationId   = null;
let currentZoneLocations = [];

const ZONE_CARD_DEFAULT  = 'border-2 border-green-200 rounded-xl px-3 py-3 text-center transition-colors bg-white hover:border-[#14532d]';
const ZONE_CARD_SELECTED = 'border-2 border-[#14532d] rounded-xl px-3 py-3 text-center transition-colors bg-green-50';
const LOC_CARD_DEFAULT   = 'border border-green-200 rounded-xl px-4 py-2.5 flex justify-between items-center transition-colors bg-white hover:border-[#14532d]';
const LOC_CARD_SELECTED  = 'border border-[#14532d] rounded-xl px-4 py-2.5 flex justify-between items-center transition-colors bg-green-50';

// ── BD Address Autofill ───────────────────────────────────────────────────
let bdDivisionId        = null;
let bdDistrictId        = null;
let bdUpazilaId         = null;
let bdUnionId           = null;
let bdUnionsForUpazila  = [];

const ADDR_PLACEHOLDERS = {
    division: 'বিভাগ বেছে নিন',
    district: 'জেলা বেছে নিন',
    upazila:  'উপজেলা বেছে নিন',
    union:    'ইউনিয়ন / এলাকা বেছে নিন',
};

function resetBdAddress() {
    bdDivisionId = bdDistrictId = bdUpazilaId = bdUnionId = null;
    bdUnionsForUpazila = [];

    ['division', 'district', 'upazila', 'union'].forEach(t => {
        const display = document.getElementById(t + '-display');
        const hidden  = document.getElementById('f-bd_' + t + '_id');
        const section = document.getElementById(t + '-section');
        const search  = document.getElementById(t + '-search');
        const list    = document.getElementById(t + '-list');
        const wrap    = document.getElementById(t + '-wrap');

        if (display) { display.textContent = ADDR_PLACEHOLDERS[t]; display.className = 'text-gray-400'; }
        if (hidden)  hidden.value = '';
        if (section) section.classList.add('hidden');
        if (search)  search.value = '';
        if (list)    list.innerHTML = '';
        if (wrap && t !== 'division') wrap.style.display = 'none';
    });
}

function openAddrSection(type) {
    // Close all sections
    ['division', 'district', 'upazila', 'union'].forEach(t => {
        const s = document.getElementById(t + '-section');
        if (s) s.classList.add('hidden');
    });

    const section = document.getElementById(type + '-section');
    if (!section) return;
    section.classList.remove('hidden');

    const search = document.getElementById(type + '-search');
    if (search) { search.value = ''; search.focus(); }
    renderAddrList(type, '');
}

function toggleAddrSection(type) {
    const section = document.getElementById(type + '-section');
    if (!section) return;
    if (section.classList.contains('hidden')) {
        openAddrSection(type);
    } else {
        section.classList.add('hidden');
    }
}

function searchAddr(type, query) {
    renderAddrList(type, query);
}

function renderAddrList(type, query) {
    const list = document.getElementById(type + '-list');
    if (!list) return;

    const q = query.trim().toLowerCase();
    let items = [];

    if (type === 'division') {
        items = q ? BD_DIVISIONS.filter(d =>
            d.name.toLowerCase().includes(q) || d.bn_name.includes(query.trim())
        ) : BD_DIVISIONS;
    } else if (type === 'district') {
        const all = BD_DISTRICTS.filter(d => d.division_id == bdDivisionId);
        items = q ? all.filter(d =>
            d.name.toLowerCase().includes(q) || d.bn_name.includes(query.trim())
        ) : all;
    } else if (type === 'upazila') {
        const all = BD_UPAZILAS.filter(u => u.district_id == bdDistrictId);
        items = q ? all.filter(u =>
            u.name.toLowerCase().includes(q) || u.bn_name.includes(query.trim())
        ) : all;
    } else if (type === 'union') {
        items = q ? bdUnionsForUpazila.filter(u =>
            u.name.toLowerCase().includes(q) || u.bn_name.includes(query.trim())
        ) : bdUnionsForUpazila;
    }

    if (items.length === 0) {
        list.innerHTML = '<div class="text-gray-400 text-sm text-center py-3 px-4">পাওয়া যায়নি।</div>';
        return;
    }

    list.innerHTML = items.map(item => `
        <button type="button"
                data-addr-type="${type}"
                data-addr-id="${item.id}"
                data-addr-name="${(item.bn_name || '').replace(/"/g,'&quot;')}"
                class="addr-opt w-full text-left px-4 py-2.5 text-sm hover:bg-green-50 border-b border-gray-50 last:border-0">
            <span class="font-medium text-[#14532d]">${item.bn_name}</span>
            <span class="text-gray-400 text-[11px] ml-1.5">${item.name}</span>
        </button>
    `).join('');

    list.querySelectorAll('.addr-opt').forEach(btn => {
        btn.addEventListener('click', () => {
            selectAddr(btn.dataset.addrType, parseInt(btn.dataset.addrId, 10), btn.dataset.addrName);
        });
    });
}

function selectAddr(type, id, displayName) {
    const display = document.getElementById(type + '-display');
    const hidden  = document.getElementById('f-bd_' + type + '_id');
    const section = document.getElementById(type + '-section');

    if (display) { display.textContent = displayName; display.className = 'text-[#14532d] font-medium text-sm'; }
    if (hidden)  hidden.value = id;
    if (section) section.classList.add('hidden');

    // Clear error
    const errEl = document.getElementById('err-bd_' + type + '_id');
    if (errEl) { errEl.textContent = ''; errEl.classList.add('hidden'); }

    if (type === 'division') {
        bdDivisionId = id;
        // Reset downstream
        bdDistrictId = bdUpazilaId = bdUnionId = null;
        bdUnionsForUpazila = [];
        ['district', 'upazila', 'union'].forEach(t => {
            const d = document.getElementById(t + '-display');
            const h = document.getElementById('f-bd_' + t + '_id');
            const s = document.getElementById(t + '-section');
            const sr = document.getElementById(t + '-search');
            const l = document.getElementById(t + '-list');
            const w = document.getElementById(t + '-wrap');
            if (d) { d.textContent = ADDR_PLACEHOLDERS[t]; d.className = 'text-gray-400'; }
            if (h) h.value = '';
            if (s) s.classList.add('hidden');
            if (sr) sr.value = '';
            if (l) l.innerHTML = '';
            if (w) w.style.display = 'none';
        });
        // Show and auto-open district
        const dw = document.getElementById('district-wrap');
        if (dw) dw.style.display = 'block';
        openAddrSection('district');

    } else if (type === 'district') {
        bdDistrictId = id;
        bdUpazilaId = bdUnionId = null;
        bdUnionsForUpazila = [];
        ['upazila', 'union'].forEach(t => {
            const d = document.getElementById(t + '-display');
            const h = document.getElementById('f-bd_' + t + '_id');
            const s = document.getElementById(t + '-section');
            const sr = document.getElementById(t + '-search');
            const l = document.getElementById(t + '-list');
            const w = document.getElementById(t + '-wrap');
            if (d) { d.textContent = ADDR_PLACEHOLDERS[t]; d.className = 'text-gray-400'; }
            if (h) h.value = '';
            if (s) s.classList.add('hidden');
            if (sr) sr.value = '';
            if (l) l.innerHTML = '';
            if (w) w.style.display = 'none';
        });
        const uw = document.getElementById('upazila-wrap');
        if (uw) uw.style.display = 'block';
        openAddrSection('upazila');

    } else if (type === 'upazila') {
        bdUpazilaId = id;
        bdUnionId = null;
        bdUnionsForUpazila = [];
        const uniDisplay = document.getElementById('union-display');
        const uniHidden  = document.getElementById('f-bd_union_id');
        const uniSection = document.getElementById('union-section');
        const uniSearch  = document.getElementById('union-search');
        const uniList    = document.getElementById('union-list');
        if (uniDisplay) { uniDisplay.textContent = ADDR_PLACEHOLDERS.union; uniDisplay.className = 'text-gray-400'; }
        if (uniHidden)  uniHidden.value = '';
        if (uniSection) uniSection.classList.add('hidden');
        if (uniSearch)  uniSearch.value = '';
        if (uniList)    uniList.innerHTML = '';
        const uw = document.getElementById('union-wrap');
        if (uw) uw.style.display = 'block';
        loadUnions(id); // AJAX + auto-open

    } else if (type === 'union') {
        bdUnionId = id;
    }
}

async function loadUnions(upazilaId) {
    bdUnionsForUpazila = [];
    const section = document.getElementById('union-section');
    const list    = document.getElementById('union-list');
    const search  = document.getElementById('union-search');

    if (section) section.classList.remove('hidden');
    if (search)  { search.value = ''; }
    if (list)    list.innerHTML = '<div class="text-gray-400 text-sm text-center py-4">লোড হচ্ছে...</div>';

    try {
        const res  = await fetch('/address/unions/' + upazilaId, { headers: { 'Accept': 'application/json' } });
        if (!res.ok) throw new Error('http-error');
        bdUnionsForUpazila = await res.json();
        renderAddrList('union', '');
        if (search) search.focus();
    } catch (_) {
        if (list) list.innerHTML = '<div class="text-red-400 text-sm text-center py-3">লোড করতে সমস্যা। আবার চেষ্টা করুন।</div>';
    }
}

function getOrderSubtotal() {
    if (fixedComboData) return fixedComboData.sell_price;
    return comboItems.reduce((s, x) => s + x.price, 0);
}

function calcDeliveryCharge(zone, location) {
    if (!zone || !location) return 0;
    const sub = getOrderSubtotal();
    if (zone.free_delivery_minimum_amount !== null && sub >= zone.free_delivery_minimum_amount) {
        return 0;
    }
    return location.delivery_charge !== null ? location.delivery_charge : zone.delivery_charge;
}

function onZoneSelect(zoneId) {
    selectedZoneId     = zoneId;
    selectedLocationId = null;
    currentZoneLocations = [];

    // Update zone card styles
    DELIVERY_ZONES.forEach(z => {
        const d = document.getElementById('zone-display-' + z.id);
        if (d) d.className = z.id === zoneId ? ZONE_CARD_SELECTED : ZONE_CARD_DEFAULT;
    });

    // Set hidden zone input
    const zInput = document.getElementById('f-delivery_zone_id');
    if (zInput) zInput.value = zoneId;

    // Clear location selection
    const lInput = document.getElementById('f-delivery_location_id');
    if (lInput) lInput.value = '';

    // Reset search
    const search = document.getElementById('location-search');
    if (search) search.value = '';

    // Load this zone's locations
    const zone = DELIVERY_ZONES.find(z => z.id === zoneId);
    currentZoneLocations = zone ? zone.locations : [];

    // Render locations
    renderLocations('');

    // Show location section
    const locSec = document.getElementById('location-section');
    if (locSec) locSec.style.display = 'block';

    // Reset delivery display
    const delivEl = document.getElementById('order-delivery-display');
    if (delivEl) { delivEl.textContent = 'এলাকা বেছে নিন'; delivEl.className = 'text-gray-400 italic'; }

    // Update total (no delivery yet)
    const tel = document.getElementById('order-total-display');
    if (tel) tel.textContent = '৳' + fmt(getOrderSubtotal() + PACKAGING_COST);
}

function filterLocations(query) {
    renderLocations(query.trim().toLowerCase());
}

function renderLocations(query) {
    const list = document.getElementById('location-list');
    if (!list) return;

    const filtered = query
        ? currentZoneLocations.filter(l =>
            l.location_name.toLowerCase().includes(query) ||
            (l.keywords && l.keywords.toLowerCase().includes(query))
          )
        : currentZoneLocations;

    if (filtered.length === 0) {
        list.innerHTML = '<p class="text-gray-400 text-sm text-center py-3">কোনো এলাকা পাওয়া যায়নি।</p>';
        return;
    }

    const zone = DELIVERY_ZONES.find(z => z.id === selectedZoneId);
    list.innerHTML = filtered.map(l => {
        const charge = l.delivery_charge !== null ? l.delivery_charge : (zone ? zone.delivery_charge : 0);
        const isSelected = l.id === selectedLocationId;
        return `<label class="block cursor-pointer">
            <input type="radio" name="_location_radio" value="${l.id}" class="sr-only"
                   onchange="onLocationSelect(${l.id})"${isSelected ? ' checked' : ''}>
            <div id="loc-display-${l.id}" class="${isSelected ? LOC_CARD_SELECTED : LOC_CARD_DEFAULT}">
                <span class="text-sm font-medium text-[#14532d]">${l.location_name}</span>
                <span class="text-xs text-gray-400 flex-shrink-0">৳${fmt(charge)}</span>
            </div>
        </label>`;
    }).join('');
}

function onLocationSelect(locationId) {
    selectedLocationId = locationId;

    // Set hidden location input
    const lInput = document.getElementById('f-delivery_location_id');
    if (lInput) lInput.value = locationId;

    // Update card styles in current render
    currentZoneLocations.forEach(l => {
        const d = document.getElementById('loc-display-' + l.id);
        if (d) d.className = l.id === locationId ? LOC_CARD_SELECTED : LOC_CARD_DEFAULT;
    });

    // Calculate charge
    const zone     = DELIVERY_ZONES.find(z => z.id === selectedZoneId);
    const location = currentZoneLocations.find(l => l.id === locationId);
    const charge   = calcDeliveryCharge(zone, location);

    // Update delivery display
    const delivEl = document.getElementById('order-delivery-display');
    if (delivEl) {
        const isFree = zone && zone.free_delivery_minimum_amount !== null && getOrderSubtotal() >= zone.free_delivery_minimum_amount;
        if (isFree) {
            delivEl.textContent = 'বিনামূল্যে ডেলিভারি!';
            delivEl.className   = 'text-green-600 font-semibold';
        } else {
            delivEl.textContent = '৳' + fmt(charge);
            delivEl.className   = '';
        }
    }

    // Update grand total
    const grand = getOrderSubtotal() + PACKAGING_COST + charge;
    const tel   = document.getElementById('order-total-display');
    if (tel) tel.textContent = '৳' + fmt(grand);
}

// ── Payment Screenshot ────────────────────────────────────────────────────
function onScreenshotChange(input) {
    const label = document.getElementById('screenshot-label-text');
    if (!label) return;
    if (input.files && input.files[0]) {
        const f = input.files[0];
        if (f.size > 2 * 1024 * 1024) {
            const err = document.getElementById('err-payment_screenshot');
            if (err) { err.textContent = 'ফাইলের সাইজ ২ MB-এর বেশি হওয়া যাবে না।'; err.classList.remove('hidden'); }
            input.value = '';
            label.textContent = 'ছবি বেছে নিন (JPG, PNG, WebP • সর্বোচ্চ ২ MB)';
            return;
        }
        label.textContent = '✓ ' + f.name;
    } else {
        label.textContent = 'ছবি বেছে নিন (JPG, PNG, WebP • সর্বোচ্চ ২ MB)';
    }
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
        `<input type="hidden" name="items[${i}][price_id]" value="${item.priceId}">`
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

        if (!fixedComboData && comboItems.length === 0) {
            const el = document.getElementById('err-items');
            if (el) { el.textContent = 'কমপক্ষে একটি পণ্য যোগ করুন।'; el.classList.remove('hidden'); }
            return;
        }

        // Validate BD address
        if (!bdDivisionId) {
            const el = document.getElementById('err-bd_division_id');
            if (el) { el.textContent = 'বিভাগ বেছে নিন।'; el.classList.remove('hidden'); }
            document.getElementById('division-btn')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        if (!bdDistrictId) {
            const el = document.getElementById('err-bd_district_id');
            if (el) { el.textContent = 'জেলা বেছে নিন।'; el.classList.remove('hidden'); }
            return;
        }
        if (!bdUpazilaId) {
            const el = document.getElementById('err-bd_upazila_id');
            if (el) { el.textContent = 'উপজেলা বেছে নিন।'; el.classList.remove('hidden'); }
            return;
        }

        // Validate zone + location selected
        if (!selectedZoneId) {
            const zErr = document.getElementById('err-delivery_zone_id');
            if (zErr) { zErr.textContent = 'ডেলিভারি জোন বেছে নিন।'; zErr.classList.remove('hidden'); }
            return;
        }
        if (!selectedLocationId) {
            const lErr = document.getElementById('err-delivery_location_id');
            if (lErr) { lErr.textContent = 'ডেলিভারি এলাকা বেছে নিন।'; lErr.classList.remove('hidden'); }
            return;
        }

        // Validate payment method selected
        const selectedMethod = form.querySelector('input[name="payment_method"]:checked');
        if (!selectedMethod) {
            const pmErr = document.getElementById('err-payment_method');
            if (pmErr) { pmErr.textContent = 'একটি পেমেন্ট পদ্ধতি বেছে নিন।'; pmErr.classList.remove('hidden'); }
            return;
        }

        if (fixedComboData) {
            const ci = document.getElementById('f-combo_id');
            if (ci) ci.value = fixedComboData.id;
            document.getElementById('order-items-hidden').innerHTML = '';
        } else {
            populateOrderItems();
        }

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

function toggleFaq(i) {
    const body = document.getElementById('faq-body-' + i);
    const icon = document.getElementById('faq-icon-' + i);
    if (!body) return;
    const open = !body.classList.contains('hidden');
    body.classList.toggle('hidden', open);
    if (icon) icon.style.transform = open ? '' : 'rotate(180deg)';
}

// ── Image Zoom Lightbox ───────────────────────────────────────────────────
let zoomImages = [];
let zoomCurIdx = 0;

function openZoom(modalSlideIndex) {
    zoomImages = modalSlides.filter(s => s.type === 'image').map(s => s.src);
    if (!zoomImages.length) return;
    if (modalSlides[modalSlideIndex]?.type !== 'image') return;

    let imgIdx = 0;
    for (let i = 0; i < modalSlideIndex; i++) {
        if (modalSlides[i].type === 'image') imgIdx++;
    }
    zoomCurIdx = imgIdx;
    zoomShowImage();
    document.getElementById('zoom-overlay').style.display = 'flex';
}

function zoomShowImage() {
    const img     = document.getElementById('zoom-img');
    const counter = document.getElementById('zoom-counter');
    const prevBtn = document.getElementById('zoom-prev');
    const nextBtn = document.getElementById('zoom-next');

    if (img) img.src = zoomImages[zoomCurIdx] || '';

    if (zoomImages.length > 1) {
        if (counter) { counter.textContent = (zoomCurIdx + 1) + ' / ' + zoomImages.length; counter.style.display = 'block'; }
        if (prevBtn) prevBtn.style.display = 'flex';
        if (nextBtn) nextBtn.style.display = 'flex';
    } else {
        if (counter) counter.style.display = 'none';
        if (prevBtn) prevBtn.style.display = 'none';
        if (nextBtn) nextBtn.style.display = 'none';
    }
}

function closeZoom() {
    const ol = document.getElementById('zoom-overlay');
    if (!ol || ol.style.display === 'none') return;
    ol.style.display = 'none';
    zoomImages = [];
}

function zoomNav(dir) {
    if (!zoomImages.length) return;
    zoomCurIdx = ((zoomCurIdx + dir) % zoomImages.length + zoomImages.length) % zoomImages.length;
    zoomShowImage();
}

// ── Card slideshow auto-cycle ─────────────────────────────────────────────
(function () {
    document.querySelectorAll('[data-slideshow]').forEach(function (el) {
        const imgs = Array.from(el.querySelectorAll('.card-slide'));
        const dots = Array.from(el.querySelectorAll('.card-dot'));
        if (imgs.length <= 1) return;
        let cur = 0;
        setInterval(function () {
            imgs[cur].style.opacity = '0';
            if (dots[cur]) dots[cur].style.opacity = '.4';
            cur = (cur + 1) % imgs.length;
            imgs[cur].style.opacity = '1';
            if (dots[cur]) dots[cur].style.opacity = '.9';
        }, 3000);
    });
})();
</script>

</body>
</html>
