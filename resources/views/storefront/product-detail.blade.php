@extends('storefront.layout')
@section('title', $product->display_name)

@php
    // Resolve a usable URL for a stored image path (local 'storage/...' or full http URL).
    $imgUrl = fn($p) => \App\Support\ProductMedia::url($p);

    // পাইকারি view shows the separate wholesale cover when one is uploaded; gallery is shared.
    $coverChannel = (($wholesaleView ?? false) || $product->isWholesale()) ? 'wholesale' : 'retail';
    $main    = $imgUrl($product->coverImage($coverChannel)) ?: asset('images/product-placeholder.svg');
    $mainAlt = $product->coverImageAlt($coverChannel);
    $gallery = collect($product->gallery_images ?? [])->map($imgUrl)->filter()->values();
    // Gallery with alt text (custom per image, else "name — ছবি N").
    $galleryItems = collect($product->gallery_images ?? [])->values()
        ->map(fn ($p, $i) => ['url' => $imgUrl($p), 'alt' => $product->imageAlt($p, $i + 2)])
        ->filter(fn ($g) => $g['url'])->values();

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

    // Meta Pixel ViewContent → platform (per scope) + this product's vendor only.
    \App\Support\MetaPixel::viewContent($product, $lowestRetail);

    // Enquiry is public (guest allowed). Autofill from the logged-in customer profile.
    $isCustomer   = auth()->check() && auth()->user()->role === 'customer';
    $authCustomer = $isCustomer ? auth()->user()->customer : null;
    $authName     = $authCustomer->name ?? (auth()->user()->name ?? '');
    $authPhone    = $authCustomer->mobile_number ?? '';
    $authAddress  = $authCustomer->last_full_address ?? '';

    // WhatsApp / Call buttons for wholesale buyers (site contact number from settings).
    $pdWaDigits = preg_replace('/\D+/', '', \App\Models\WebsiteSetting::get('whatsapp_number'));
    $pdWa       = $pdWaDigits === '' ? '' : (str_starts_with($pdWaDigits, '0') ? '88'.$pdWaDigits : $pdWaDigits);
    $pdWaLink   = $pdWa ? 'https://wa.me/'.$pdWa.'?text='.rawurlencode('আসসালামু আলাইকুম, আমি "'.($product->name_bn ?: $product->name_en).'" পাইকারি কিনতে চাই। দাম জানাবেন?'."\n".url()->current()) : '';

    // SEO: custom per-product values when set, otherwise the auto fallbacks (see Product::seo*()).
    $customTitle = trim((string) $product->meta_title);
    $metaDesc    = $product->seoDescription();
    $keywords    = $product->seoKeywords();
    $canonical   = $product->seoCanonical();
    $ogTitle     = $customTitle ?: $product->seoTitle().' — '.$siteName;
@endphp

{{-- ─────────────────────────── SEO head ─────────────────────────── --}}
@if($customTitle !== '')
@section('seo_title', $customTitle)
@endif
@section('meta_description', $metaDesc)
@section('head')
    @if($product->meta_robots)
    <meta name="robots" content="{{ $product->meta_robots }}">
    @endif
    @if($keywords)
    <meta name="keywords" content="{{ $keywords }}">
    @endif
    <link rel="canonical" href="{{ $canonical }}">

    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $metaDesc }}">
    <meta property="og:image" content="{{ $product->seoImage() }}">
    <meta property="og:image:alt" content="{{ $product->ogImageAlt() }}">
    <meta name="twitter:image:alt" content="{{ $product->ogImageAlt() }}">
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
        'brand'       => ['@type' => 'Brand', 'name' => $product->brand ?: $siteName],
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
        .pd-thumb.active, .pd-thumb:focus { border-color: #14532d !important; outline-offset: 3px; }
        .pd-pack.active { background:#14532d !important; border-color:#14532d !important; }
        .pd-tab { color:#4b5563; background:#f3f4f6; }
        .pd-tab.active { color:#fff; background:#14532d; }
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
        <div class="product-detail-frame rounded-xl overflow-hidden border border-gray-100 bg-gray-50 aspect-square">
            <button type="button" id="pd-image-pane" class="product-image-stage pd-pane" data-product-image-stage aria-label="Open product image viewer">
            <img id="pd-main-image" src="{{ $main }}" alt="{{ $mainAlt }}"
                 class="product-detail-artwork">
            </button>
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

        <p class="text-xs text-gray-500 mt-2">ছবিতে মাউস রাখলে জুম হবে · বড় করে দেখতে চাপুন</p>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" data-product-thumbnail="{{ $main }}" aria-label="Show main product image" aria-pressed="true"
                    class="pd-thumb active w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent">
                <img src="{{ $main }}" alt="{{ $mainAlt }}" class="w-full h-full product-artwork" loading="lazy" decoding="async">
            </button>
            @foreach($galleryItems as $g)
            <button type="button" data-product-thumbnail="{{ $g['url'] }}" data-product-alt="{{ $g['alt'] }}" aria-label="Show product image {{ $loop->iteration + 1 }}" aria-pressed="false"
                    class="pd-thumb w-16 h-16 rounded-lg overflow-hidden border-2 border-transparent">
                <img src="{{ $g['url'] }}" alt="{{ $g['alt'] }}" class="w-full h-full product-artwork" loading="lazy" decoding="async">
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
        @if($product->tags->isNotEmpty())
        <ul class="flex flex-wrap gap-2 my-3" aria-label="Product tags">
            @foreach($product->tags as $tag)<li class="text-xs text-gray-700 bg-gray-100 rounded-full px-3 py-1">{{ $tag->name }}</li>@endforeach
        </ul>
        @endif
        @if($product->name_en)
            <p class="text-sm text-gray-400 mt-0.5">{{ $product->name_en }}</p>
        @endif

        @if($product->vendor)
            <p class="text-xs text-gray-500 mt-2">সরবরাহকারী: <span class="font-medium text-gray-700">{{ $product->vendor->shop_name ?? $product->vendor->name ?? $siteName }}</span></p>
        @endif

        @if($product->short_description)
            <p class="text-sm text-gray-600 mt-4 leading-relaxed">{{ $product->short_description }}</p>
        @endif

        @if($product->description)
        <a href="#pd-tabs" onclick="pdShowTab('desc')" class="inline-block text-xs font-semibold text-[#14532d] mt-2 hover:underline">বিস্তারিত বিবরণ দেখুন ↓</a>
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
                            <img src="{{ $vUrl }}" alt="{{ $variant->imageAlt($product->display_name) }}" class="w-7 h-7 rounded-md product-artwork border border-gray-100">
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

        {{-- ── (c) RETAIL purchase block (pack → price → quantity → actions) ── --}}
        @if($hasRetail)
        <div class="mt-6 border-t border-gray-100 pt-5">
            <div class="flex items-end gap-2 flex-wrap">
                <div id="pd-sel-price" class="text-[#d97706] font-serif-bn text-3xl font-bold leading-none">৳{{ number_format($retailPacks->first()->final_price, 0) }}</div>
                <div id="pd-unit-note" class="text-xs text-gray-400 pb-0.5 hidden"></div>
            </div>

            <h2 class="text-sm font-semibold text-gray-700 mt-5 mb-2">প্যাক / ওজন</h2>
            <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
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

            {{-- Quantity: how many of the selected pack (2 × 1kg = 2kg) --}}
            <div class="flex items-center gap-4 mt-5">
                <span class="text-sm font-semibold text-gray-700 w-20">পরিমাণ:</span>
                <div class="flex items-center border border-gray-300 rounded-xl overflow-hidden">
                    <button type="button" onclick="pdChangeQty(-1)" aria-label="কমান"
                            class="w-10 h-10 text-xl text-gray-600 hover:bg-gray-100 disabled:opacity-30" id="pd-qty-minus" disabled>−</button>
                    <input type="number" id="pd-qty" value="1" min="1" max="{{ \App\Services\CheckoutService::MAX_PACKS_PER_LINE }}" inputmode="numeric"
                           onchange="pdSetPackQty(this.value)" aria-label="পরিমাণ"
                           class="w-12 h-10 text-center font-semibold text-gray-800 border-x border-gray-200 focus:outline-none [appearance:textfield] [&::-webkit-inner-spin-button]:appearance-none">
                    <button type="button" onclick="pdChangeQty(1)" aria-label="বাড়ান"
                            class="w-10 h-10 text-xl text-gray-600 hover:bg-gray-100">+</button>
                </div>
                <span id="pd-qty-total" class="text-xs text-gray-500"></span>
            </div>

            <div class="grid grid-cols-2 gap-2.5 mt-5">
                <button type="button" onclick="pdAddToCart(false)" {{ $product->isInStock() ? '' : 'disabled' }}
                        class="inline-flex items-center justify-center gap-2 bg-[#c9a227] hover:bg-[#b8921d] text-[#0f3d22] font-bold text-sm py-3 rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    কার্টে যোগ করুন
                </button>
                <button type="button" onclick="pdAddToCart(true)" {{ $product->isInStock() ? '' : 'disabled' }}
                        class="bg-[#0f3d22] hover:bg-[#14532d] text-white font-bold text-sm py-3 rounded-xl transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                    এখনই কিনুন
                </button>
                @if($pdWa)
                <a href="{{ $pdWaLink }}" id="pd-wa-order" target="_blank" rel="noopener"
                   class="inline-flex items-center justify-center gap-2 bg-[#22a35a] hover:bg-[#1c8a4b] text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347M12.05 21.785h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                    WhatsApp-এ অর্ডার
                </a>
                <a href="tel:+{{ $pdWa }}"
                   class="inline-flex items-center justify-center gap-2 bg-[#1e3a8a] hover:bg-[#1e40af] text-white font-semibold text-sm py-3 rounded-xl transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    কল করে অর্ডার
                </a>
                @endif
            </div>

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
                @if($pdWa)
                <a href="{{ $pdWaLink }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 border border-[#25D366] text-[#128C7E] hover:bg-[#25D366] hover:text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                    WhatsApp করুন
                </a>
                <a href="tel:+{{ $pdWa }}"
                   class="inline-flex items-center gap-1.5 bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold text-sm px-5 py-2.5 rounded-xl transition-colors whitespace-nowrap">
                    📞 কল করুন
                </a>
                @endif
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
                আপনার তথ্য, দাম এবং payment record নিরাপদ রাখতে {{ $siteName }} chatbox এবং order process ব্যবহার করুন।
            </p>

            {{-- In-page enquiry form (slide section, NOT a popup) — guest + logged-in --}}
            <div id="pd-enquiry-form" class="{{ $errors->any() && old('quantity_kg') ? '' : 'hidden' }} mt-4">
                @if(\App\Support\AuthSettings::guestBlocked('enquiry'))
                    @include('partials.storefront.guest-login-prompt', ['feature' => 'enquiry'])
                @endif
                <form action="{{ route('products.enquiry.store', $product->slug) }}" method="POST" class="space-y-3" @if(\App\Support\AuthSettings::guestBlocked('enquiry')) hidden @endif>
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
                        <label class="block text-xs text-gray-500 mb-1">ডেলিভারি এলাকা <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                        <input type="text" name="delivery_location" value="{{ old('delivery_location', $authAddress) }}" placeholder="যেমন: ঢাকা, চট্টগ্রাম"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    </div>

                    {{-- Extra details stay folded so the form is quick: qty + name + phone is enough. --}}
                    <details class="rounded-lg border border-gray-100 bg-white px-3 py-2" @if(old('customer_email') || old('business_type') || old('message')) open @endif>
                        <summary class="cursor-pointer text-xs font-semibold text-[#14532d]">আরও তথ্য দিন (ঐচ্ছিক) — ইমেইল, ব্যবসার ধরন, বার্তা</summary>
                        <div class="space-y-3 mt-3">
                    {{-- Email (optional) — enquiry tracking link + quote updates by email --}}
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">ইমেইল <span class="text-gray-300">(ঐচ্ছিক)</span></label>
                        <input type="email" name="customer_email" value="{{ old('customer_email') }}" placeholder="you@example.com"
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <p class="text-[11px] text-gray-400 mt-1">ইমেইল দিলে enquiry track করার লিংক ও কোটেশনের আপডেট email-এ পাবেন।</p>
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
                        </div>
                    </details>

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

{{-- ── (e) Tabs: Description / Reviews ──────────────────────────────────── --}}
@php
    $pdHasDesc   = filled($product->description);
    $starCounts  = collect(range(5, 1))->mapWithKeys(fn ($n) => [$n => $reviews->where('rating', $n)->count()]);
@endphp
<div id="pd-tabs" class="mt-8 bg-white rounded-2xl border border-gray-100 shadow-sm p-2 flex gap-2" role="tablist">
    @if($pdHasDesc)
    <button type="button" role="tab" data-pd-tab="desc" onclick="pdShowTab('desc')"
            class="pd-tab px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">পণ্যের বিবরণ</button>
    @endif
    <button type="button" role="tab" data-pd-tab="reviews" onclick="pdShowTab('reviews')"
            class="pd-tab px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">গ্রাহক রিভিউ ({{ $reviewCount }})</button>
</div>

@if($pdHasDesc)
<div id="pd-panel-desc" data-pd-panel="desc" class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-8">
    <h2 class="text-base font-bold text-gray-800 inline-block border-b-2 border-[#c9a227] pb-1.5 mb-4">পণ্যের বিস্তারিত</h2>
    <div class="text-[15px] text-gray-700 leading-7 max-w-none">{!! nl2br(e($product->description)) !!}</div>
</div>
@endif

<div id="reviews" data-pd-panel="reviews" class="mt-4 bg-white rounded-2xl border border-gray-100 shadow-sm p-5 sm:p-8">
    {{-- Rating summary + per-star breakdown --}}
    <div class="grid grid-cols-1 md:grid-cols-[220px_1fr] gap-6 items-center mb-6 pb-6 border-b border-gray-100">
        <div class="text-center md:text-left">
            <div class="text-5xl font-bold text-gray-900 leading-none">{{ number_format((float) $avgRating, 1) }}</div>
            <div class="text-amber-400 text-xl leading-none mt-2">
                @for($s = 1; $s <= 5; $s++)<span class="{{ $s <= round($avgRating) ? '' : 'text-gray-200' }}">★</span>@endfor
            </div>
            <div class="text-xs text-gray-400 mt-1">{{ $reviewCount }} টি রিভিউ</div>
        </div>
        <div class="space-y-1.5">
            @foreach($starCounts as $stars => $count)
            @php $pct = $reviewCount ? round($count / $reviewCount * 100) : 0; @endphp
            <div class="flex items-center gap-3 text-xs">
                <span class="w-10 text-gray-500 whitespace-nowrap">{{ $stars }} ★</span>
                <div class="flex-1 h-2 rounded-full bg-gray-100 overflow-hidden"><div class="h-full bg-amber-400 rounded-full" style="width: {{ $pct }}%"></div></div>
                <span class="w-9 text-right text-gray-400">{{ $pct }}%</span>
            </div>
            @endforeach
        </div>
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
                <img src="{{ $imgUrl($review->image) }}" alt="রিভিউ ছবি" loading="lazy" decoding="async" class="mt-2 w-20 h-20 rounded-lg object-cover border border-gray-100">
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
        @if(\App\Support\AuthSettings::guestBlocked('review'))
            <div class="max-w-xl">@include('partials.storefront.guest-login-prompt', ['feature' => 'review', 'back' => request()->getRequestUri().'#reviews'])</div>
        @endif
        <form action="{{ route('products.reviews.store', $product->slug) }}" method="POST" enctype="multipart/form-data" class="space-y-3 max-w-xl" @if(\App\Support\AuthSettings::guestBlocked('review')) hidden @endif>
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
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" {{ \App\Support\ImageOptimizer::inputAttributes('review') }}
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
            $rpImg = $imgUrl($rp->coverImage($relatedWholesale ? 'wholesale' : 'retail')) ?: asset('images/product-placeholder.svg');
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
                <img src="{{ $rpImg }}" alt="{{ $rp->display_name }}" class="w-full h-full product-artwork" loading="lazy">
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

{{-- ── Sticky mobile action bar (sits just above the 57px bottom nav) ──────── --}}
<div class="lg:hidden fixed left-0 right-0 z-30 bg-white border-t border-gray-200 px-4 py-2.5 flex gap-2 shadow-[0_-4px_12px_rgba(0,0,0,0.06)]"
     style="bottom: calc(57px + env(safe-area-inset-bottom));">
    @if($hasRetail)
        <button type="button" onclick="pdAddToCart(false)" {{ $product->isInStock() ? '' : 'disabled' }}
                class="flex-1 border-2 border-[#14532d] text-[#14532d] font-semibold text-sm py-2.5 rounded-xl disabled:opacity-40">কার্টে যোগ</button>
        <button type="button" onclick="pdAddToCart(true)" {{ $product->isInStock() ? '' : 'disabled' }}
                class="flex-1 bg-[#0f3d22] text-white font-bold text-sm py-2.5 rounded-xl disabled:opacity-40">এখনই কিনুন</button>
        @if($pdWa)
        <a href="{{ $pdWaLink }}" target="_blank" rel="noopener" aria-label="WhatsApp-এ অর্ডার"
           onclick="const o=document.getElementById('pd-wa-order'); if (o) this.href=o.href;"
           class="w-11 shrink-0 flex items-center justify-center bg-[#22a35a] text-white rounded-xl">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.91C21.95 6.45 17.5 2 12.04 2zm5.8 14.03c-.25.69-1.44 1.32-1.99 1.41-.51.08-1.15.11-1.86-.12-.43-.14-.98-.32-1.69-.62-2.97-1.28-4.91-4.27-5.06-4.47-.15-.2-1.21-1.6-1.21-3.06 0-1.46.76-2.17 1.04-2.47.27-.3.59-.37.79-.37h.57c.18 0 .43-.07.67.51.25.59.84 2.05.91 2.2.07.15.12.32.02.52-.1.2-.15.32-.3.49-.15.17-.31.39-.44.52-.15.15-.3.31-.13.61.17.3.77 1.27 1.65 2.06 1.13 1.01 2.09 1.32 2.39 1.47.3.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.3.39-.25.67-.15.27.1 1.73.82 2.03.97.3.15.49.22.57.35.07.12.07.71-.18 1.39z"/></svg>
        </a>
        @endif
    @elseif($showWholesale)
        <button type="button" onclick="pdToggleEnquiry(true)"
                class="flex-1 bg-[#14532d] text-white font-semibold text-sm py-2.5 rounded-xl">দাম জানুন</button>
        @if($pdWa)
        <a href="{{ $pdWaLink }}" target="_blank" rel="noopener"
           class="flex-1 text-center border border-[#25D366] text-[#128C7E] font-semibold text-sm py-2.5 rounded-xl">WhatsApp</a>
        <a href="tel:+{{ $pdWa }}"
           class="flex-1 text-center bg-[#c9a227] text-[#0f3d22] font-semibold text-sm py-2.5 rounded-xl">কল করুন</a>
        @endif
    @endif
</div>
<div class="lg:hidden h-20"></div>{{-- spacer so sticky bar never covers content --}}

@endsection

@section('scripts')
<script>
    // ── Gallery ───────────────────────────────────────────────────────────
    function pdHideAllPanes() { document.querySelectorAll('.pd-pane').forEach(el => el.classList.add('hidden')); }
    function pdSetActiveThumb(btn) {
        document.querySelectorAll('.pd-thumb').forEach(t => { t.classList.remove('active'); t.setAttribute('aria-pressed', 'false'); });
        if (btn) { btn.classList.add('active'); btn.setAttribute('aria-pressed', 'true'); }
    }
    function pdShowImage(src, btn) {
        pdHideAllPanes();
        const img = document.getElementById('pd-main-image');
        img.src = src; document.getElementById('pd-image-pane').classList.remove('hidden');
        pdSetActiveThumb(btn);
    }
    function pdShowPane(id, btn) {
        pdHideAllPanes();
        const pane = document.getElementById(id);
        if (pane) pane.classList.remove('hidden');
        pdSetActiveThumb(btn);
    }
    // ── Description / Reviews tabs ───────────────────────────────────────
    function pdShowTab(name) {
        if (!document.querySelector('[data-pd-panel="' + name + '"]')) name = 'reviews';
        document.querySelectorAll('[data-pd-panel]').forEach(p => p.classList.toggle('hidden', p.dataset.pdPanel !== name));
        document.querySelectorAll('.pd-tab').forEach(t => {
            const on = t.dataset.pdTab === name;
            t.classList.toggle('active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
    }
    // Default: description; #reviews links (review form errors, guest prompt) open reviews.
    pdShowTab(window.location.hash === '#reviews' || @json($errors->has('comment') || $errors->has('rating')) ? 'reviews' : 'desc');

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
        if (typeof pdRefreshPrice === 'function') pdRefreshPrice();

        // Swap the main image to the variant photo (restore product image when none).
        const img = document.getElementById('pd-main-image');
        if (img) {
            pdHideAllPanes();
            document.getElementById('pd-image-pane').classList.remove('hidden');
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
        pdRefreshPrice();
    }

    // ── Quantity (packs of the selected size) ─────────────────────────────
    const PD_MAX_QTY = {{ \App\Services\CheckoutService::MAX_PACKS_PER_LINE }};
    let pdQty = 1;
    const pdTaka = n => '৳' + Math.round(n).toLocaleString('en-US');
    function pdSetPackQty(q) {
        pdQty = Math.min(PD_MAX_QTY, Math.max(1, parseInt(q, 10) || 1));
        const inp = document.getElementById('pd-qty'); if (inp) inp.value = pdQty;
        const minus = document.getElementById('pd-qty-minus'); if (minus) minus.disabled = pdQty <= 1;
        pdRefreshPrice();
    }
    function pdChangeQty(d) { pdSetPackQty(pdQty + d); }

    // Big price = pack price × quantity; keeps the WhatsApp order text in sync.
    function pdRefreshPrice() {
        const active = document.querySelector('.pd-pack.active');
        if (!active) return;
        const unit  = parseFloat(active.dataset.price) || 0;
        const label = (active.querySelector('.pd-pack-lbl') || {}).textContent || '';
        const sp = document.getElementById('pd-sel-price');
        if (sp) sp.textContent = pdTaka(unit * pdQty);
        const note = document.getElementById('pd-unit-note');
        if (note) { note.textContent = '(' + label.trim() + ' ' + pdTaka(unit) + ' × ' + pdQty + ')'; note.classList.toggle('hidden', pdQty <= 1); }
        const wa = document.getElementById('pd-wa-order');
        if (wa) {
            const base = wa.href.split('?')[0];
            const variant = pdSelectedVariantName ? ' (' + pdSelectedVariantName + ')' : '';
            wa.href = base + '?text=' + encodeURIComponent(
                'আসসালামু আলাইকুম, আমি অর্ডার করতে চাই:\n' +
                @json($product->name_bn ?: $product->name_en) + variant + ' — ' + label.trim() + ' × ' + pdQty + ' = ' + pdTaka(unit * pdQty) +
                '\n' + @json(url()->current()));
        }
    }
    document.addEventListener('DOMContentLoaded', pdRefreshPrice);

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
                price:       active ? parseFloat(active.dataset.price) : 0,
                qty:         pdQty
            });
            if (buyNow) { msCartCheckout(); return; }
            msToast('🛒 কার্টে যোগ হয়েছে' + (pdQty > 1 ? ' (' + pdQty + 'টি)' : ''));
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
