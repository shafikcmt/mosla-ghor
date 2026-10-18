@if($heroSlides->isNotEmpty())
    @include('partials.storefront.home-slider')
@else
    @include('partials.storefront.home-hero-fallback')
@endif
@include('partials.storefront.home-trust')

<section class="hm-container hm-section" id="categories" aria-labelledby="category-title">
    <div class="hm-section-heading"><h2 id="category-title">ক্যাটাগরি অনুযায়ী কিনুন</h2><a href="{{ $retailHref }}">সব পণ্য →</a></div>
    @if($navCategories->isNotEmpty())
    {{-- Picture tiles in a swipeable row (wraps on wide screens). --}}
    <div class="hm-cat-row">
        <a data-mode-link href="{{ url('/') }}{{ $modeQuery ? '?'.$modeQuery : '' }}#products" class="hm-cat" @if(! $selectedCategory) aria-current="page" @endif>
            <span class="hm-cat-img hm-cat-all" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z"/></svg></span>
            <span class="hm-cat-name">সব পণ্য</span>
            <span class="hm-cat-count">{{ \App\Support\BanglaNumber::digits((string) ($allProductCount ?? 0)) }}টি পণ্য</span>
        </a>
        @foreach($navCategories as $navCat)
        @php $thumb = ($categoryThumbs ?? [])[$navCat->id] ?? null; $count = ($categoryCounts ?? [])[$navCat->id] ?? 0; @endphp
        <a data-mode-link href="{{ url('/') }}?category={{ urlencode($navCat->slug) }}{{ $modeQuery ? '&'.$modeQuery : '' }}#products" class="hm-cat" @if($selectedCategory?->id === $navCat->id) aria-current="page" @endif>
            <span class="hm-cat-img" aria-hidden="true">
                @if($thumb)<img src="{{ $thumb }}" alt="" loading="lazy" decoding="async" width="88" height="88">@else{{ mb_substr($navCat->name_bn, 0, 1) }}@endif
            </span>
            <span class="hm-cat-name">{{ $navCat->name_bn }}</span>
            @if($count)<span class="hm-cat-count">{{ \App\Support\BanglaNumber::digits((string) $count) }}টি পণ্য</span>@endif
        </a>
        @endforeach
    </div>
    @else
    <p class="hm-empty">সব পণ্য একসাথে দেখতে নিচের তালিকা ব্যবহার করুন।</p>
    @endif
</section>
