@php
    $previewWholesale = $previewMode === 'wholesale';
    $previewUrl = route('products.show', ['product'=>$product->slug, 'mode'=>$previewWholesale ? 'wholesale' : 'retail']);
@endphp
<article class="hm-product-tile" data-discovery-product="{{ $product->id }}" data-channel="{{ $previewMode }}">
    <a class="hm-tile-image" href="{{ $previewUrl }}"><img class="product-artwork" src="{{ \App\Support\ProductMedia::url($product->main_image) ?: asset('images/product-placeholder.svg') }}" alt="{{ $product->display_name }}" loading="lazy" decoding="async" width="400" height="300"></a>
    <div class="hm-tile-body">
        <span class="hm-channel-label">{{ $previewWholesale ? 'পাইকারি' : 'খুচরা' }}</span>
        <h3><a href="{{ $previewUrl }}">{{ $product->display_name }}</a></h3>
        @if($previewWholesale)
            <p>{{ $product->moqLabel() ? 'সর্বনিম্ন: '.$product->moqLabel() : 'পরিমাণ অনুযায়ী কোটেশন' }}</p>
            @if($product->delivery_time)<p>{{ $product->delivery_time }}</p>@endif
        @elseif($initRetailPrices->isNotEmpty())
            <p class="hm-price">৳{{ number_format($initRetailPrices->first()->final_price, 0) }} <small>{{ $initRetailPrices->first()->label }} থেকে</small></p>
        @else
            <p>প্যাক ও বিস্তারিত দেখুন</p>
        @endif
        @if(!$product->isInStock())<small>স্টক শেষ</small>@endif
        @if($product->vendor_id)<small>মার্কেটপ্লেস পণ্য</small>@endif
        <a class="hm-tile-action" href="{{ $previewUrl }}">{{ $previewWholesale ? 'বিস্তারিত ও enquiry' : 'প্যাক বেছে নিন' }}</a>
    </div>
</article>
