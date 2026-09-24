@if($heroSlides->isNotEmpty())
    @include('partials.storefront.home-slider')
@else
    @include('partials.storefront.home-hero-fallback')
@endif
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
