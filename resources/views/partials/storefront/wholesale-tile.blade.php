{{-- Text-first পাইকারি card for the home page: what a business buyer decides on
     (MOQ, delivery, quick quote), no photo. Needs $product. --}}
@php
    $wtUrl = route('products.show', ['product' => $product->slug, 'mode' => 'wholesale']);
    $wtCat = $product->cat;
@endphp
<article class="hm-ws-tile" data-discovery-product="{{ $product->id }}" data-channel="wholesale">
    <div class="hm-ws-top">
        <span class="hm-ws-cat">{{ $wtCat ? ($wtCat->name_bn ?: $wtCat->name_en) : 'পাইকারি' }}</span>
        @unless($product->isInStock())<span class="hm-ws-out">স্টক শেষ</span>@endunless
    </div>
    <h3><a href="{{ $wtUrl }}">{{ $product->display_name }}</a></h3>
    @if($product->short_description)
        <p class="hm-ws-desc">{{ \Illuminate\Support\Str::limit(strip_tags($product->short_description), 140) }}</p>
    @endif
    <div class="hm-ws-facts">
        <span class="hm-ws-fact hm-ws-moq">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            সর্বনিম্ন অর্ডার: <b>{{ $product->moqLabel() ?: 'যেকোনো পরিমাণ' }}</b>
        </span>
        @if($product->delivery_time)
        <span class="hm-ws-fact">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 7h11v9H3zM14 10h4l3 3v3h-7M7.5 19a1.5 1.5 0 100-3 1.5 1.5 0 000 3zm10 0a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"/></svg>
            ডেলিভারি: <b>{{ $product->delivery_time }}</b>
        </span>
        @endif
        <span class="hm-ws-fact hm-ws-quote">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 7h6m-6 4h6m-6 4h4M6 3h12v18l-3-2-3 2-3-2-3 2z"/></svg>
            দাম: <b>পরিমাণ অনুযায়ী কোটেশন</b>
        </span>
    </div>
    <div class="hm-ws-actions">
        @include('partials.storefront.wholesale-quick-actions')
    </div>
    <a class="hm-ws-more" href="{{ $wtUrl }}">বিস্তারিত দেখুন →</a>
</article>
