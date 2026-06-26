@extends('storefront.layout')
@section('title', $product->display_name)

@php
    // Resolve a usable URL for a stored image path (local 'storage/...' or full http URL).
    $imgUrl = fn($p) => $p ? (\Illuminate\Support\Str::startsWith($p, 'http') ? $p : asset($p)) : null;

    $main    = $imgUrl($product->main_image) ?: 'https://placehold.co/600x600/f1f5f3/14532d?text=' . urlencode($product->display_name);
    $gallery = collect($product->gallery_images ?? [])->map($imgUrl)->filter()->values();

    // Build a YouTube embed URL from a watch/share link, if present.
    $youtubeEmbed = null;
    if ($product->video_url && preg_match('~(?:youtube\.com/(?:watch\?v=|embed/)|youtu\.be/)([\w-]{11})~', $product->video_url, $m)) {
        $youtubeEmbed = 'https://www.youtube.com/embed/' . $m[1];
    }
    $localVideo = ($product->video_path && \Illuminate\Support\Str::startsWith($product->video_path, 'storage/'))
        ? asset($product->video_path) : null;

    $cat = $product->cat; // Category model (null-safe accessor)

    // Wholesale view: either the product is flagged wholesale, OR we are on the
    // dedicated /wholesale/products/{slug} URL (passed from the controller).
    $wholesaleView = $wholesaleView ?? false;
    // Paykari/wholesale products NEVER show price — enquiry only.
    $isWholesale = $product->isWholesale() || $wholesaleView;

    // Direct (non-variant) pack prices drive the on-page purchase / enquiry UI.
    $retailPacks    = $retailPrices->whereNull('product_variant_id')->values();
    $wholesalePacks = $wholesalePrices->whereNull('product_variant_id')->values();
    // A wholesale product hides its retail price entirely, regardless of price rows.
    $hasRetail      = ! $isWholesale && $retailPacks->isNotEmpty();
    $hasWholesale   = $wholesalePacks->isNotEmpty();
    // A wholesale product (or wholesale-mode view) has NO price, so the enquiry
    // block is its only call to action — always show it. (Previously this was gated
    // by wholesale_enquiry_enabled, which left products with the toggle off showing
    // a price-less page and no way to enquire.) Retail products still only show the
    // wholesale block when they actually carry wholesale price rows.
    $showWholesale  = $isWholesale ? true : $hasWholesale;
    $lowestRetail   = $hasRetail ? (float) $retailPacks->min('final_price') : null;
    $moqLabel       = $product->moqLabel();

    // Enquiry is public (guest allowed). Autofill from the logged-in customer profile.
    $isCustomer   = auth()->check() && auth()->user()->role === 'customer';
    $authCustomer = $isCustomer ? auth()->user()->customer : null;
    $authName     = $authCustomer->name ?? (auth()->user()->name ?? '');
    $authPhone    = $authCustomer->mobile_number ?? '';
    $authAddress  = $authCustomer->last_full_address ?? '';

    $metaDesc   = \Illuminate\Support\Str::limit(strip_tags($product->short_description ?: $product->description ?: $product->display_name), 155);
    $canonical  = route('products.show', $product->slug);
@endphp

{{-- ─────────────────────────── SEO head ─────────────────────────── --}}
@section('head')
    <meta name="description" content="{{ $metaDesc }}">
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->display_name }} — মসলা ঘর">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:image" content="{{ $main }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta name="twitter:card" content="summary_large_image">

    <script type="application/ld+json">
    {!! json_encode(array_filter([
        '@context'    => 'https://schema.org/',
        '@type'       => 'Product',
        'name'        => $product->display_name,
        'image'       => $main,
        'description' => $metaDesc,
        'sku'         => $product->sku,
        'brand'       => ['@type' => 'Brand', 'name' => $product->brand ?: 'মসলা ঘর'],
        'category'    => $cat?->name_bn,
        'offers'      => $hasRetail ? [
            '@type'         => 'Offer',
            'priceCurrency' => 'BDT',
            'price'         => $lowestRetail,
            'availability'  => $product->isInStock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            'url'           => $canonical,
        ] : null,
        'aggregateRating' => $reviewCount > 0 ? [
            '@type'       => 'AggregateRating',
            'ratingValue' => $avgRating,
            'reviewCount' => $reviewCount,
        ] : null,
        'review' => $reviewCount > 0 ? $reviews->take(5)->map(fn($r) => [
            '@type'         => 'Review',
            'reviewRating'  => ['@type' => 'Rating', 'ratingValue' => $r->rating, 'bestRating' => 5],
            'author'        => ['@type' => 'Person', 'name' => $r->display_name],
            'reviewBody'    => $r->comment,
            'datePublished' => $r->created_at?->toDateString(),
        ])->values()->all() : null,
    ], fn($v) => !is_null($v)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}
    </script>

    <style>
        .pd-thumb.active, .pd-thumb:focus { border-color: #14532d !important; outline: none; }
        .pd-pack.active { background:#14532d !important; border-color:#14532d !important; }
        .pd-pack.active .pd-pack-lbl { color:#86efac; }
        .pd-pack.active .pd-pack-val { color:#fff; }
        .star-input input { display:none; }
        .star-input label { cursor:pointer; font-size:1.75rem; color:#e5e7eb; transition:color .1s; }
        .star-input:hover label,
        .star-input label:hover ~ label { color:#e5e7eb; }
        .star-input label.on { color:#f59e0b; }
    </style>
@endsection

@section('content')

{{-- Breadcrumb + easy retail/wholesale navigation --}}
<div class="flex flex-wrap items-center justify-between gap-2 mb-4">
    <nav class="text-xs text-gray-400 flex flex-wrap items-center gap-1.5">
        <a href="/" class="hover:text-[#14532d]">হোম</a>
        <span>/</span>
        @if($wholesaleView)
            <a href="/?tab=wholesale" class="hover:text-[#14532d]">পাইকারি পণ্য</a>
            <span>/</span>
        @endif
        @if($cat)
            @if($cat->parent)
                <a href="/?category={{ $cat->parent->slug }}" class="hover:text-[#14532d]">{{ $cat->parent->name_bn }}</a>
                <span>/</span>
            @endif
            <a href="/?category={{ $cat->slug }}" class="hover:text-[#14532d]">{{ $cat->name_bn }}</a>
            <span>/</span>
        @endif
        <span class="text-gray-700 font-medium">{{ $product->display_name }}</span>
    </nav>
    <div class="flex items-center gap-2 text-xs">
        <a href="/" class="text-gray-500 hover:text-[#14532d]">← সব পণ্য</a>
        {{-- Switch between retail and wholesale view of the same product (only when not a wholesale-only product) --}}
        @unless($product->isWholesale())
            @if($wholesaleView)
                <a href="{{ route('products.show', $product->slug) }}"
                   class="font-semibold text-[#14532d] bg-green-50 px-2.5 py-1 rounded-full">খুচরা হিসেবে দেখুন</a>
            @else
                <a href="{{ route('customer.wholesale.products.show', $product->slug) }}"
                   class="font-semibold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-full">পাইকারি হিসেবে দেখুন</a>
            @endif
        @endunless
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-7">

    {{-- ── (a) Media gallery ──────────────────────────────────────────────── --}}
    <div>
        <div class="rounded-xl overflow-hidden border border-gray-100 bg-gray-50 aspect-square">
            <img id="pd-main-image" src="{{ $main }}" alt="{{ $product->display_name }}"
                 class="w-full h-full object-cover pd-pane">
            @if($youtubeEmbed)
            <div id="pd-youtube-pane" class="pd-pane hidden w-full h-full">
                <iframe class="w-full h-full" src="{{ $youtubeEmbed }}" title="{{ $product->display_name }} ভিডিও"
                        frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            @endif
            @if($localVideo)
            <div id="pd-video-pane" class="pd-pane hidden w-full h-full bg-black">
                <video class="w-full h-full" src="{{ $localVideo }}" controls></video>
            </div>
            @endif
        </div>

        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" onclick="pdShowImage('{{ $main }}', this)"
                    class="pd-thumb active w-16 h-16 rounded-lg overflow-hidden border-2 border-[#14532d]">
                <img src="{{ $main }}" alt="{{ $product->display_name }}" class="w-full h-full object-cover">
            </button>
            @foreach($gallery as $g)
            <button type="button" onclick="pdShowImage('{{ $g }}', this)"
                    class="pd-thumb w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent">
                <img src="{{ $g }}" alt="{{ $product->display_name }}" class="w-full h-full object-cover">
            </button>
            @endforeach
            @if($youtubeEmbed)
            <button type="button" onclick="pdShowPane('pd-youtube-pane', this)"
                    class="pd-thumb w-16 h-16 rounded-lg border-2 border-transparent bg-red-50 flex items-center justify-center text-red-600" title="ভিডিও">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
            @endif
            @if($localVideo)
            <button type="button" onclick="pdShowPane('pd-video-pane', this)"
                    class="pd-thumb w-16 h-16 rounded-lg border-2 border-transparent bg-gray-100 flex items-center justify-center text-gray-600" title="ভিডিও">
                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </button>
            @endif
        </div>
    </div>

    {{-- ── (b) Product info ───────────────────────────────────────────────── --}}
    <div>
        <div class="flex flex-wrap items-center gap-2 mb-2">
            @if($cat)
                <span class="text-xs font-medium text-[#14532d] bg-green-50 px-2.5 py-1 rounded-full">{{ $cat->name_bn }}</span>
            @endif
            @if($isWholesale || $hasWholesale)
                <span class="text-xs font-semibold text-orange-700 bg-orange-50 px-2.5 py-1 rounded-full">পাইকারি</span>
            @endif
            @if($hasRetail)
                <span class="text-xs font-semibold text-[#14532d] bg-green-50 px-2.5 py-1 rounded-full">খুচরা</span>
            @endif
            <span class="text-xs font-bold px-2.5 py-1 rounded-full {{ $product->isInStock() ? 'bg-[#c9a227]/15 text-[#9a7a10]' : 'bg-red-50 text-red-600' }}">
                {{ $product->isInStock() ? 'স্টকে আছে' : 'স্টক শেষ' }}
            </span>
        </div>

        <h1 class="font-serif-bn text-2xl sm:text-3xl font-bold text-[#14532d] leading-tight">{{ $product->name_bn }}</h1>
        @if($product->name_en)
            <p class="text-sm text-gray-400 mt-0.5">{{ $product->name_en }}</p>
        @endif

        @if($product->vendor)
            <p class="text-xs text-gray-500 mt-2">সরবরাহকারী: <span class="font-medium text-gray-700">{{ $product->vendor->shop_name ?? $product->vendor->name ?? 'মসলা ঘর' }}</span></p>
        @endif

        @if($product->short_description)
            <p class="text-sm text-gray-600 mt-4 leading-relaxed">{{ $product->short_description }}</p>
        @endif

        @if($product->description)
        <div class="mt-3">
            <div id="pd-desc" class="text-sm text-gray-600 leading-relaxed overflow-hidden transition-all" style="max-height: 4.5rem;">
                {!! nl2br(e($product->description)) !!}
            </div>
            <button type="button" id="pd-desc-toggle" onclick="pdToggleDesc()"
                    class="text-xs font-semibold text-[#14532d] mt-1 hover:underline">আরও পড়ুন ▾</button>
        </div>
        @endif

        @if($product->brand || $product->unit)
        <dl class="mt-4 grid grid-cols-2 gap-y-1.5 text-sm">
            @if($product->brand)<dt class="text-gray-400">ব্র্যান্ড</dt><dd class="text-gray-700 font-medium">{{ $product->brand }}</dd>@endif
            @if($product->unit)<dt class="text-gray-400">একক</dt><dd class="text-gray-700 font-medium">{{ $product->unit }}</dd>@endif
        </dl>
        @endif

        {{-- ── (b2) VARIANT selector (always visible when variants exist) ──── --}}
        @if($product->activeVariants->isNotEmpty())
        <div id="pd-variant-block" class="mt-5">
            <h2 class="text-sm font-bold text-gray-800 mb-2">ভ্যারিয়েন্ট নির্বাচন করুন <span class="text-red-500">*</span></h2>
            <div class="flex flex-wrap gap-2">
                @foreach($product->activeVariants as $i => $variant)
                    @php
                        $vUrl   = $variant->imageUrl();
                        $vPrice = $isWholesale ? null : $variant->effectiveRetailPrice();
                        $isSel  = $i === 0; // activeVariants is ordered default-first
                    @endphp
                    <button type="button"
                            class="pd-variant flex items-center gap-2 border rounded-xl px-3 py-2 text-sm transition-colors {{ $isSel ? 'border-[#14532d] bg-[#f0faf4] text-[#14532d] font-semibold' : 'border-gray-200 text-gray-700 hover:border-[#14532d]' }}"
                            data-id="{{ $variant->id }}"
                            data-name="{{ $variant->name }}"
                            data-image="{{ $vUrl ?: '' }}"
                            data-price="{{ $vPrice !== null ? $vPrice : '' }}"
                            onclick="pdSelectVariant(this)">
                        @if($vUrl)
                            <img src="{{ $vUrl }}" alt="{{ $variant->name }}" class="w-7 h-7 rounded-md object-cover border border-gray-100">
                        @endif
                        <span>{{ $variant->name }}</span>
                        @if($vPrice !== null)
                            <span class="text-[11px] text-[#c9a227] font-bold">৳{{ number_format($vPrice, 0) }}</span>
                        @endif
                    </button>
                @endforeach
            </div>
            <input type="hidden" id="pd-variant-id" value="{{ $product->activeVariants->first()->id }}">
            <input type="hidden" id="pd-variant-name" value="{{ $product->activeVariants->first()->name }}">
        </div>
        @endif

        {{-- ── (c) RETAIL purchase block ──────────────────────────────────── --}}
        @if($hasRetail)
        <div class="mt-6">
            <h2 class="text-sm font-bold text-gray-800 mb-2">খুচরা মূল্য</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2 mb-3">
                @foreach($retailPacks as $i => $price)
                <button type="button"
                        class="pd-pack {{ $i === 0 ? 'active' : '' }} border border-green-100 bg-[#f0faf4] rounded-lg p-2 text-center transition-colors"
                        data-price-id="{{ $price->id }}" data-price="{{ (float) $price->final_price }}"
                        onclick="pdSelectPack(this)">
                    <div class="pd-pack-lbl text-gray-500 text-[11px] leading-tight">{{ $price->label }}</div>
                    <div class="pd-pack-val text-[#14532d] text-sm font-semibold leading-tight mt-0.5">৳{{ number_format($price->final_price, 0) }}</div>
                </button>
                @endforeach
            </div>

            <div class="flex items-center gap-3">
                <div class="text-gray-500 text-sm">নির্বাচিত মূল্য:</div>
                <div id="pd-sel-price" class="text-[#c9a227] font-serif-bn text-2xl font-bold">৳{{ number_format($retailPacks->first()->final_price, 0) }}</div>
            </div>

            <div class="flex gap-3 mt-4">
                <button type="button" onclick="pdAddToCart(false)" {{ $product->isInStock() ? '' : 'disabled' }}
                        class="flex-1 border-2 border-[#14532d] text-[#14532d] hover:bg-[#14532d] hover:text-white font-semibold text-sm py-3 rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    🛒 কার্টে যোগ করুন
                </button>
                <button type="button" onclick="pdAddToCart(true)" {{ $product->isInStock() ? '' : 'disabled' }}
                        class="flex-1 btn-gold text-[#0f3d22] font-bold text-sm py-3 rounded-xl disabled:opacity-40 disabled:cursor-not-allowed">
                    এখনই কিনুন
                </button>
            </div>
            <p class="text-[11px] text-gray-400 mt-2">কার্টে যোগ করলে উপরের ব্যাগ আইকনে দেখতে পাবেন।</p>

            {{-- Combo / box cross-sell CTA --}}
            <div class="mt-4 flex items-center justify-between gap-3 rounded-xl border border-[#c9a227]/40 bg-amber-50/50 px-4 py-3">
                <span class="text-sm text-[#14532d] font-medium">একাধিক পণ্য একসাথে অর্ডার করতে চান?</span>
                <a href="/#combo-builder"
                   class="shrink-0 bg-[#14532d] hover:bg-[#166534] text-white text-xs font-semibold px-3 py-2 rounded-lg transition-colors whitespace-nowrap">
                    কম্বো / বক্স তৈরি করুন
                </a>
            </div>
        </div>
        @endif

        {{-- ── (d) WHOLESALE / PAYKARI enquiry block (price never shown) ──── --}}
        @if($showWholesale)
        <div id="enquiry" class="mt-6 rounded-xl border border-orange-100 bg-orange-50/40 p-4">
            <h2 class="text-sm font-bold text-orange-800 mb-1">পাইকারি / Wholesale</h2>
            <p class="text-sm text-gray-700">বড় পরিমাণে কিনতে চাইলে পাইকারি দাম জানতে পারবেন।</p>
            <p class="text-xs text-gray-500 mt-1">পাইকারি দামের জন্য quantity অনুযায়ী আমাদের team quote দিবে।</p>

            {{-- MOQ / delivery / payment info --}}
            @if($moqLabel || $product->delivery_time || $product->payment_terms)
            <dl class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
                @if($moqLabel)
                <div class="bg-white rounded-lg border border-orange-100 px-3 py-2">
                    <dt class="text-[11px] text-gray-400">Minimum Order Quantity</dt>
                    <dd class="font-semibold text-gray-800">{{ $moqLabel }}</dd>
                </div>
                @endif
                @if($product->delivery_time)
                <div class="bg-white rounded-lg border border-orange-100 px-3 py-2">
                    <dt class="text-[11px] text-gray-400">ডেলিভারি সময়</dt>
                    <dd class="font-semibold text-gray-800">{{ $product->delivery_time }}</dd>
                </div>
                @endif
                @if($product->payment_terms)
                <div class="bg-white rounded-lg border border-orange-100 px-3 py-2">
                    <dt class="text-[11px] text-gray-400">পেমেন্ট শর্ত (নমুনা)</dt>
                    <dd class="font-semibold text-gray-800">{{ $product->payment_terms }}</dd>
                </div>
                @endif
            </dl>
            @endif

            <div class="flex flex-wrap gap-3 mt-3">
                <button type="button" onclick="pdToggleEnquiry(true)"
                        class="btn-gold text-[#0f3d22] font-bold text-sm px-5 py-2.5 rounded-xl whitespace-nowrap">
                    পাইকারি দাম জানুন
                </button>
                <button type="button" onclick="pdToggleEnquiry(true)"
                        class="bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                    দাম জিজ্ঞাসা করুন
                </button>
                <button type="button" onclick="pdToggleEnquiry(true)"
                        class="border border-[#14532d] text-[#14532d] hover:bg-green-50 font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                    বিক্রেতার সাথে কথা বলুন
                </button>
                <button type="button" id="pd-add-bag"
                        data-id="{{ $product->id }}" data-slug="{{ $product->slug }}"
                        data-name="{{ $product->display_name }}" data-image="{{ $main }}"
                        data-qty="{{ $product->min_order_quantity ?: 1 }}" data-unit="{{ $product->min_order_unit ?: 'kg' }}"
                        data-min="{{ $product->min_order_quantity ?: '' }}" data-min-unit="{{ $product->min_order_unit ?: '' }}"
                        class="border border-amber-500 text-amber-700 hover:bg-amber-50 font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors">
                    🛍️ পাইকারি তালিকায় যোগ করুন
                </button>
            </div>

            {{-- Bulk pricing note + platform-safety message --}}
            <p class="text-xs text-gray-500 mt-3">
                পরিমাণ বেশি হলে দাম কম হতে পারে। আপনার চাহিদা অনুযায়ী আমরা দাম জানাবো।
            </p>
            <p class="text-[11px] text-orange-700 bg-orange-50 border border-orange-100 rounded-lg px-3 py-2 mt-2 leading-snug">
                আপনার তথ্য, দাম এবং payment record নিরাপদ রাখতে MoslaMart chatbox এবং order process ব্যবহার করুন।
            </p>

            {{-- In-page enquiry form (slide section, NOT a popup) — guest + logged-in --}}
            <div id="pd-enquiry-form" class="{{ $errors->any() && old('quantity_kg') ? '' : 'hidden' }} mt-4">
                <form action="{{ route('products.enquiry.store', $product->slug) }}" method="POST" class="space-y-3">
                    @csrf
                    @if($wholesaleView)<input type="hidden" name="from_wholesale" value="1">@endif

                    {{-- Selected variant is mirrored here from the variant selector above. --}}
                    @if($product->activeVariants->isNotEmpty())
                    <input type="hidden" name="product_variant_id" id="pd-enquiry-variant-id"
                           value="{{ old('product_variant_id', $product->activeVariants->first()->id) }}">
                    <p class="text-xs text-gray-500">নির্বাচিত ভ্যারিয়েন্ট: <span id="pd-enquiry-variant-label" class="font-semibold text-[#14532d]">{{ $product->activeVariants->first()->name }}</span></p>
                    @endif

                    {{-- Quantity + unit (required) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">পরিমাণ <span class="text-red-500">*</span></label>
                            <input type="number" name="quantity_kg" min="{{ $product->min_order_quantity ?: 0.01 }}" step="0.01"
                                   value="{{ old('quantity_kg', $product->min_order_quantity ?: '') }}" required placeholder="যেমন: 50"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            @if($moqLabel)<p class="text-[11px] text-gray-400 mt-1">সর্বনিম্ন: {{ $moqLabel }}</p>@endif
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">একক <span class="text-red-500">*</span></label>
                            <select name="quantity_unit"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                                @php $defUnit = old('quantity_unit', $product->min_order_unit ?: 'kg'); @endphp
                                @foreach(['kg' => 'কেজি (kg)', 'bag' => 'বস্তা (bag)', 'carton' => 'কার্টন (carton)', 'piece' => 'পিস (piece)'] as $val => $lbl)
                                    <option value="{{ $val }}" {{ $defUnit === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    {{-- Quick wholesale quantity chips (set quantity + unit) --}}
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" onclick="pdSetQty(5,'kg')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">৫kg</button>
                        <button type="button" onclick="pdSetQty(10,'kg')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">১০kg</button>
                        <button type="button" onclick="pdSetQty(25,'kg')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">২৫kg</button>
                        <button type="button" onclick="pdSetQty(50,'kg')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">৫০kg</button>
                        <button type="button" onclick="pdSetQty(1,'bag')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">১ বস্তা</button>
                        <button type="button" onclick="pdSetQty(2,'bag')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">২ বস্তা</button>
                        <button type="button" onclick="pdSetQty(1,'carton')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">১ কার্টন</button>
                        <button type="button" onclick="pdSetQty(2,'carton')" class="px-3 py-1.5 rounded-full text-xs font-semibold border border-amber-200 text-amber-800 hover:bg-amber-50 transition">২ কার্টন</button>
                    </div>

                    {{-- Name + phone (required; autofilled for logged-in customers) --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">নাম <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_name" value="{{ old('customer_name', $authName) }}" required
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ফোন / WhatsApp <span class="text-red-500">*</span></label>
                            <input type="text" name="customer_phone" value="{{ old('customer_phone', $authPhone) }}" required placeholder="01XXXXXXXXX"
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        </div>
                    </div>

                    {{-- Delivery location (required; autofilled if available) --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ডেলিভারি ঠিকানা / এলাকা <span class="text-red-500">*</span></label>
                        <input type="text" name="delivery_location" value="{{ old('delivery_location', $authAddress) }}" required placeholder="যেমন: ঢাকা, চট্টগ্রাম"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    </div>

                    {{-- Optional --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">ব্যবসার ধরন <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                            <select name="business_type"
                                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                                <option value="">— বেছে নিন —</option>
                                <option value="shop" @selected(old('business_type')==='shop')>শপ / দোকান</option>
                                <option value="restaurant" @selected(old('business_type')==='restaurant')>রেস্তোরাঁ</option>
                                <option value="dealer" @selected(old('business_type')==='dealer')>ডিলার</option>
                                <option value="retailer" @selected(old('business_type')==='retailer')>রিটেইলার</option>
                                <option value="other" @selected(old('business_type')==='other')>অন্যান্য</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">বার্তা <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                            <input type="text" name="message" value="{{ old('message') }}" placeholder="বিশেষ প্রয়োজনীয়তা..."
                                   class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                        Send Enquiry / দাম জানুন
                    </button>
                    @guest
                    <p class="text-[11px] text-gray-400 text-center">লগইন ছাড়াই enquiry পাঠাতে পারবেন। পরে একই ফোন নম্বরে account তৈরি করে status দেখতে পারবেন।</p>
                    @endguest
                </form>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── (e) Reviews ────────────────────────────────────────────────────────── --}}
<div id="reviews" class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-7">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
        <h2 class="text-lg font-bold text-gray-800">গ্রাহক রিভিউ</h2>
        @if($reviewCount > 0)
        <div class="flex items-center gap-2">
            <div class="text-amber-400 text-lg leading-none">
                @for($s = 1; $s <= 5; $s++)<span class="{{ $s <= round($avgRating) ? '' : 'text-gray-200' }}">★</span>@endfor
            </div>
            <span class="text-sm font-semibold text-gray-700">{{ $avgRating }}</span>
            <span class="text-sm text-gray-400">({{ $reviewCount }} টি রিভিউ)</span>
        </div>
        @endif
    </div>

    {{-- Approved reviews list --}}
    @if($reviewCount > 0)
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-8">
        @foreach($reviews as $review)
        <div class="border border-gray-100 rounded-xl p-4">
            <div class="flex items-center gap-1 text-amber-400 leading-none mb-2">
                @for($s = 1; $s <= 5; $s++)<span class="{{ $s <= $review->rating ? '' : 'text-gray-200' }}">★</span>@endfor
            </div>
            @if($review->comment)
                <p class="text-sm text-gray-700 leading-relaxed">{{ $review->comment }}</p>
            @endif
            @if($review->image)
                <img src="{{ $imgUrl($review->image) }}" alt="রিভিউ ছবি" class="mt-2 w-20 h-20 rounded-lg object-cover border border-gray-100">
            @endif
            <div class="flex items-center gap-2 mt-3 pt-2 border-t border-gray-50">
                <div class="w-8 h-8 rounded-full bg-[#14532d] flex items-center justify-center flex-shrink-0">
                    <span class="text-[#c9a227] font-bold text-sm">{{ mb_substr($review->display_name, 0, 1) }}</span>
                </div>
                <div>
                    <div class="text-sm font-semibold text-gray-800">{{ $review->display_name }}</div>
                    <div class="text-[11px] text-gray-400">{{ $review->created_at?->format('d M Y') }}</div>
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @else
    <p class="text-sm text-gray-400 mb-8">এখনও কোনো রিভিউ নেই। প্রথম রিভিউটি আপনিই দিন!</p>
    @endif

    {{-- Submit review form --}}
    <div class="border-t border-gray-100 pt-6">
        <h3 class="text-base font-bold text-gray-800 mb-3">একটি রিভিউ লিখুন</h3>
        <form action="{{ route('products.reviews.store', $product->slug) }}" method="POST" enctype="multipart/form-data" class="space-y-3 max-w-xl">
            @csrf

            <div>
                <label class="block text-xs text-gray-500 mb-1">রেটিং <span class="text-red-500">*</span></label>
                <div class="star-input inline-flex flex-row-reverse" id="pd-stars">
                    @for($s = 5; $s >= 1; $s--)
                    <input type="radio" name="rating" id="pd-star-{{ $s }}" value="{{ $s }}" {{ $s === 5 ? 'checked' : '' }}>
                    <label for="pd-star-{{ $s }}" data-val="{{ $s }}" onclick="pdSetStars({{ $s }})">★</label>
                    @endfor
                </div>
            </div>

            @unless($isCustomer)
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">ফোন/ইমেইল (ঐচ্ছিক, গোপন থাকবে)</label>
                    <input type="text" name="customer_contact" value="{{ old('customer_contact') }}"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                </div>
            </div>
            @endunless

            <div>
                <label class="block text-xs text-gray-500 mb-1">আপনার মতামত <span class="text-red-500">*</span></label>
                <textarea name="comment" rows="3" required placeholder="পণ্যটি সম্পর্কে আপনার অভিজ্ঞতা লিখুন..."
                          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] resize-none">{{ old('comment') }}</textarea>
            </div>

            <div>
                <label class="block text-xs text-gray-500 mb-1">ছবি (ঐচ্ছিক)</label>
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-green-50 file:text-[#14532d] file:text-sm file:font-semibold">
            </div>

            <button type="submit"
                    class="bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm px-6 py-2.5 rounded-xl transition-colors">
                রিভিউ জমা দিন
            </button>
            <p class="text-[11px] text-gray-400">রিভিউ অনুমোদনের পর প্রকাশিত হবে।</p>
        </form>
    </div>
</div>

{{-- ── (f) Related products ───────────────────────────────────────────────── --}}
@if($relatedProducts->isNotEmpty())
<div class="mt-8">
    <h2 class="text-lg font-bold text-gray-800 mb-3">{{ $relatedWholesale ? 'আরও পাইকারি পণ্য' : 'সম্পর্কিত পণ্য' }}</h2>
    <div class="flex gap-4 overflow-x-auto pb-3" style="scroll-snap-type: x mandatory;">
        @foreach($relatedProducts as $rp)
        @php
            $rpImg = $rp->main_image
                ? (\Illuminate\Support\Str::startsWith($rp->main_image, 'http') ? $rp->main_image : asset($rp->main_image))
                : 'https://placehold.co/300x300/f1f5f3/14532d?text=' . urlencode($rp->display_name);
            $rpUrl = $relatedWholesale
                ? route('customer.wholesale.products.show', $rp->slug)
                : route('products.show', $rp->slug);
            // For retail related cards, show the cheapest active retail pack price.
            $rpPrice = $relatedWholesale ? null : $rp->activeRetailPrices->min('final_price');
        @endphp
        <a href="{{ $rpUrl }}"
           class="flex-shrink-0 w-40 bg-white rounded-xl border border-gray-100 shadow-sm hover:shadow-md hover:-translate-y-0.5 transition-all"
           style="scroll-snap-align: start;">
            <div class="aspect-square rounded-t-xl overflow-hidden bg-gray-50">
                <img src="{{ $rpImg }}" alt="{{ $rp->display_name }}" class="w-full h-full object-cover">
            </div>
            <div class="p-2.5">
                <p class="text-sm font-medium text-gray-800 truncate">{{ $rp->name_bn }}</p>
                @if(! $relatedWholesale && $rpPrice)
                    <p class="text-sm text-[#c9a227] font-bold font-serif-bn mt-0.5">৳{{ number_format($rpPrice, 0) }} <span class="text-gray-400 text-[10px] font-sans">থেকে</span></p>
                @endif
                <p class="text-xs {{ $relatedWholesale ? 'text-orange-700' : 'text-[#14532d]' }} mt-1 font-semibold">
                    {{ $relatedWholesale ? 'পাইকারি দাম জানুন →' : 'বিস্তারিত দেখুন →' }}
                </p>
            </div>
        </a>
        @endforeach
    </div>
</div>
@endif

{{-- ── Sticky mobile action bar ───────────────────────────────────────────── --}}
<div class="lg:hidden fixed bottom-0 left-0 right-0 z-30 bg-white border-t border-gray-200 px-4 py-2.5 flex gap-2 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]">
    @if($hasRetail)
        <button type="button" onclick="pdAddToCart(false)" {{ $product->isInStock() ? '' : 'disabled' }}
                class="flex-1 border-2 border-[#14532d] text-[#14532d] font-semibold text-sm py-2.5 rounded-xl disabled:opacity-40">কার্টে যোগ</button>
        <button type="button" onclick="pdAddToCart(true)" {{ $product->isInStock() ? '' : 'disabled' }}
                class="flex-1 btn-gold text-[#0f3d22] font-bold text-sm py-2.5 rounded-xl disabled:opacity-40">এখনই কিনুন</button>
    @elseif($showWholesale)
        <button type="button" onclick="pdToggleEnquiry(true)"
                class="flex-1 bg-[#14532d] text-white font-semibold text-sm py-2.5 rounded-xl">পাইকারি দাম জানুন</button>
    @endif
</div>
<div class="lg:hidden h-16"></div>{{-- spacer so sticky bar never covers content --}}

@endsection

@section('scripts')
<script>
    // ── Gallery ───────────────────────────────────────────────────────────
    function pdHideAllPanes() { document.querySelectorAll('.pd-pane').forEach(el => el.classList.add('hidden')); }
    function pdSetActiveThumb(btn) {
        document.querySelectorAll('.pd-thumb').forEach(t => t.classList.remove('active'));
        if (btn) btn.classList.add('active');
    }
    function pdShowImage(src, btn) {
        pdHideAllPanes();
        const img = document.getElementById('pd-main-image');
        img.src = src; img.classList.remove('hidden');
        pdSetActiveThumb(btn);
    }
    function pdShowPane(id, btn) {
        pdHideAllPanes();
        const pane = document.getElementById(id);
        if (pane) pane.classList.remove('hidden');
        pdSetActiveThumb(btn);
    }
    function pdToggleDesc() {
        const d = document.getElementById('pd-desc');
        const btn = document.getElementById('pd-desc-toggle');
        if (d.dataset.open === '1') { d.style.maxHeight = '4.5rem'; d.dataset.open = '0'; btn.textContent = 'আরও পড়ুন ▾'; }
        else { d.style.maxHeight = d.scrollHeight + 'px'; d.dataset.open = '1'; btn.textContent = 'কম দেখুন ▴'; }
    }

    // ── Variant selection (image swap + carry id into cart/enquiry) ────────
    const pdMainDefaultSrc = (document.getElementById('pd-main-image') || {}).src || '';
    let pdSelectedVariantId   = (document.getElementById('pd-variant-id') || {}).value || null;
    let pdSelectedVariantName = (document.getElementById('pd-variant-name') || {}).value || null;
    function pdSelectVariant(btn) {
        document.querySelectorAll('.pd-variant').forEach(b => {
            b.classList.remove('border-[#14532d]', 'bg-[#f0faf4]', 'text-[#14532d]', 'font-semibold');
            b.classList.add('border-gray-200', 'text-gray-700');
        });
        btn.classList.add('border-[#14532d]', 'bg-[#f0faf4]', 'text-[#14532d]', 'font-semibold');
        btn.classList.remove('border-gray-200', 'text-gray-700');

        pdSelectedVariantId   = btn.dataset.id || null;
        pdSelectedVariantName = btn.dataset.name || null;

        // Mirror into the enquiry form + its label.
        const hid = document.getElementById('pd-enquiry-variant-id');
        if (hid) hid.value = pdSelectedVariantId || '';
        const lbl = document.getElementById('pd-enquiry-variant-label');
        if (lbl) lbl.textContent = pdSelectedVariantName || '';
        const vid = document.getElementById('pd-variant-id');   if (vid) vid.value = pdSelectedVariantId || '';
        const vnm = document.getElementById('pd-variant-name'); if (vnm) vnm.value = pdSelectedVariantName || '';

        // Swap the main image to the variant photo (restore product image when none).
        const img = document.getElementById('pd-main-image');
        if (img) {
            pdHideAllPanes();
            img.classList.remove('hidden');
            img.src = btn.dataset.image ? btn.dataset.image : pdMainDefaultSrc;
            pdSetActiveThumb(null);
        }
    }

    // ── Retail pack selection ─────────────────────────────────────────────
    let pdSelectedPriceId = @json($hasRetail ? $retailPacks->first()->id : null);
    function pdSelectPack(btn) {
        document.querySelectorAll('.pd-pack').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        pdSelectedPriceId = parseInt(btn.dataset.priceId, 10);
        const sp = document.getElementById('pd-sel-price');
        if (sp) sp.textContent = '৳' + Math.round(parseFloat(btn.dataset.price)).toLocaleString('en-US');
    }

    // ── Add to cart / Buy now ─────────────────────────────────────────────
    // Add straight into the shared persistent cart and open the drawer (no full
    // page reload). Falls back to the legacy home-page handoff if the shared
    // store is somehow unavailable.
    function pdAddToCart(buyNow) {
        if (!pdSelectedPriceId) return;

        if (window.msCart) {
            const active = document.querySelector('.pd-pack.active');
            const lblEl  = active ? active.querySelector('.pd-pack-lbl') : null;
            msCart.add({
                productId:   @json($product->id),
                priceId:     pdSelectedPriceId,
                variantId:   pdSelectedVariantId ? parseInt(pdSelectedVariantId, 10) : null,
                variantName: pdSelectedVariantName,
                sellType:    'retail',
                nameBn:      @json($product->name),
                label:       lblEl ? lblEl.textContent.trim() : '',
                price:       active ? parseFloat(active.dataset.price) : 0
            });
            if (buyNow) { msCartCheckout(); return; }
            msToast('🛒 কার্টে যোগ হয়েছে');
            msCartOpen('retail');
            return;
        }

        // Legacy fallback: stash a pending item and let the home builder pick it up.
        try {
            localStorage.setItem('ms_pending_box_item', JSON.stringify({
                productId: @json($product->id),
                priceId:   pdSelectedPriceId,
                variantId: null,
                sellType:  'retail',
                buyNow:    !!buyNow
            }));
        } catch (e) {}
        window.location.href = '/';
    }

    // ── Wholesale enquiry form toggle (in-page, no popup) ─────────────────
    function pdToggleEnquiry(forceOpen) {
        const f = document.getElementById('pd-enquiry-form');
        if (!f) return;
        if (forceOpen) f.classList.remove('hidden');
        else f.classList.toggle('hidden');
        if (!f.classList.contains('hidden')) {
            f.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }

    // ── Review star picker ────────────────────────────────────────────────
    function pdSetStars(val) {
        document.querySelectorAll('#pd-stars label').forEach(l => {
            l.classList.toggle('on', parseInt(l.dataset.val, 10) <= val);
        });
    }
    pdSetStars(5);

    // ── Quick wholesale quantity chips → set the enquiry form's qty + unit ──
    function pdSetQty(qty, unit) {
        const qEl = document.querySelector('#pd-enquiry-form [name="quantity_kg"]');
        const uEl = document.querySelector('#pd-enquiry-form [name="quantity_unit"]');
        if (qEl) qEl.value = qty;
        if (uEl) uEl.value = unit;
    }

    // ── Add to Enquiry Bag (uses shared msBagAdd from the layout) ─────────
    // Prefer the quantity/unit the user picked in the form (or via the chips);
    // fall back to the product MOQ defaults. Opens the drawer for instant feedback.
    (function () {
        const b = document.getElementById('pd-add-bag');
        if (b) b.addEventListener('click', function () {
            const qEl = document.querySelector('#pd-enquiry-form [name="quantity_kg"]');
            const uEl = document.querySelector('#pd-enquiry-form [name="quantity_unit"]');
            const qty  = (qEl && parseFloat(qEl.value) > 0) ? parseFloat(qEl.value) : (parseFloat(b.dataset.qty) || 5);
            const unit = (uEl && uEl.value) ? uEl.value : (b.dataset.unit || 'kg');
            msBagAdd(parseInt(b.dataset.id, 10), b.dataset.slug, b.dataset.name, b.dataset.image,
                     qty, unit,
                     parseFloat(b.dataset.min) || null, b.dataset.minUnit || null,
                     pdSelectedVariantId ? parseInt(pdSelectedVariantId, 10) : null, pdSelectedVariantName);
            if (window.msCartOpen) msCartOpen('paykari');
        });
    })();

    // Arriving via a "দর জানতে চাই" / "Contact Supplier" card link (#enquiry) →
    // reveal the enquiry form right away.
    if (window.location.hash === '#enquiry' && typeof pdToggleEnquiry === 'function') {
        pdToggleEnquiry(true);
    }
</script>
@endsection
