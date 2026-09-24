@php
    $heroProducts = $featuredProducts->filter(fn ($item) => filled($item->main_image))->take(3);
    // Ignore the obsolete shipped campaign badge; custom admin badges remain supported.
    $heroBadge = trim($ws['hero_badge_text'] ?? '');
    if ($heroBadge === 'ঈদ স্পেশাল কালেকশন') { $heroBadge = ''; }
    $heroBanner = \App\Support\ProductMedia::url($ws['hero_image_url'] ?? null);
@endphp
<section class="hm-container hm-hero" aria-labelledby="home-title">
    <div class="hm-hero-copy">
        <p class="hm-campaign">{{ $heroBadge ?: 'খুচরা ও পাইকারি মসলার বিশ্বস্ত ঠিকানা' }}</p>
        <h1 id="home-title">{{ trim($ws['hero_title'] ?? '') ?: 'খাঁটি মসলা, এখন আরও সহজে' }}</h1>
        <p class="hm-hero-description">{{ \Illuminate\Support\Str::limit(trim($ws['hero_subtitle'] ?? '') ?: 'ঘরের জন্য খুচরা প্যাক, ব্যবসার জন্য বাল্ক অর্ডার। প্যাকের দাম দেখুন অথবা পাইকারি কোটেশন নিন।', 160) }}</p>
        <div class="hm-actions">
            <a class="hm-button" href="{{ $retailHref }}" onclick="return msHeroClick(event, 'retail')">{{ trim($ws['primary_cta_text'] ?? '') ?: 'পণ্য দেখুন' }}</a>
            <a class="hm-button hm-button-outline" href="{{ $wholesaleHref }}" onclick="return msHeroClick(event, 'wholesale')">পাইকারি দেখুন</a>
        </div>
        <div class="hm-hero-chips"><span>খুচরা প্যাক</span><span>পাইকারি অর্ডার</span><a href="{{ route('track-order') }}">অর্ডার ট্র্যাকিং</a></div>
    </div>
    @if($heroProducts->isNotEmpty())
    <div class="hm-hero-display hm-hero-products" data-hero-count="{{ $heroProducts->count() }}">
        @foreach($heroProducts as $heroProduct)
        <a class="hm-hero-product" href="{{ route('products.show', ['product'=>$heroProduct->slug, 'mode'=>$heroProduct->show_in_retail ? 'retail' : 'wholesale']) }}">
            <img class="product-artwork" src="{{ \App\Support\ProductMedia::url($heroProduct->main_image) }}" alt="{{ $heroProduct->display_name }}" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.svg') }}'" @if($loop->first) fetchpriority="high" @else loading="lazy" @endif>
            <span>{{ $heroProduct->display_name }}</span>
        </a>
        @endforeach
    </div>
    @elseif($heroBanner)
    <div class="hm-hero-display hm-hero-banner"><img class="product-artwork" src="{{ $heroBanner }}" alt="{{ $siteName }} — {{ $ws['hero_title'] ?? 'পণ্য সংগ্রহ' }}" fetchpriority="high"></div>
    @else
    <div class="hm-hero-display hm-hero-placeholder" aria-hidden="true">
        <svg viewBox="0 0 480 310" fill="none" xmlns="http://www.w3.org/2000/svg">
            <ellipse cx="246" cy="275" rx="188" ry="16" fill="#dfe5d8"/>
            <path d="M176 39h130l15 229H160z" fill="#f5e8c6" stroke="#b69550" stroke-width="2"/>
            <path d="M179 49h124M177 58h128" stroke="#b69550" stroke-width="3"/>
            <rect x="180" y="109" width="122" height="99" rx="3" fill="#14532d"/>
            <path d="M241 183v-51m0 30c-27 0-34-16-34-24 23 0 34 11 34 24zm0-12c25 0 30-15 30-22-20 0-30 10-30 22z" stroke="#dac380" stroke-width="3"/>
            <path d="M70 145h77l11 126H58z" fill="#e7c68c" stroke="#b69550" stroke-width="2"/>
            <rect x="73" y="183" width="69" height="54" rx="3" fill="#875621"/>
            <rect x="328" y="163" width="89" height="106" rx="17" fill="#ecd9b0" stroke="#b69550" stroke-width="2"/>
            <rect x="327" y="151" width="91" height="23" rx="5" fill="#14532d"/>
            <rect x="337" y="193" width="71" height="49" rx="3" fill="#fffaf0"/>
        </svg>
        <span>খুচরা প্যাক · বাল্ক অর্ডার</span>
    </div>
    @endif
</section>
<section class="hm-container hm-section" id="categories" aria-labelledby="category-title">
    <div class="hm-section-heading"><h2 id="category-title">ক্যাটাগরি অনুযায়ী কিনুন</h2><a href="{{ $retailHref }}">সব পণ্য</a></div>
    @if($navCategories->isNotEmpty())
    <div class="hm-categories">
        @foreach($navCategories as $navCat)
        <a data-mode-link href="{{ url('/') }}?category={{ urlencode($navCat->slug) }}{{ $modeQuery ? '&'.$modeQuery : '' }}#products" @if($selectedCategory?->id === $navCat->id) aria-current="page" @endif>
            <span class="hm-category-symbol" aria-hidden="true">{{ mb_substr($navCat->name_bn, 0, 1) }}</span><span>{{ $navCat->name_bn }}</span>
        </a>
        @endforeach
    </div>
    @else
    <p class="hm-empty">সব পণ্য একসাথে দেখতে নিচের তালিকা ব্যবহার করুন।</p>
    @endif
</section>
