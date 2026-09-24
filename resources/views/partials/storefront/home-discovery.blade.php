@php $wholesalePreview = $products->where('show_in_wholesale', true)->take(8); @endphp
<section class="hm-wholesale" id="wholesale-products" aria-labelledby="wholesale-title">
    <div class="hm-container">
        <div class="hm-section-heading">
            <div><h2 id="wholesale-title">পাইকারি ও বাল্ক অর্ডার</h2><p>ব্যবসা, দোকান, রেস্টুরেন্ট ও ডিস্ট্রিবিউটরদের জন্য পাইকারি মসলা।</p></div>
            <a href="{{ $wholesaleHref }}" onclick="return msHeroClick(event, 'wholesale')">সব পাইকারি পণ্য দেখুন</a>
        </div>
        @if($wholesalePreview->isNotEmpty())
        <div class="hm-product-grid">
            @foreach($wholesalePreview as $previewProduct)
                @include('partials.storefront.product-card', ['product'=>$previewProduct, 'previewMode'=>'wholesale'])
            @endforeach
        </div>
        @else
        <p class="hm-empty">এই বিভাগে এখন পাইকারি পণ্য নেই। অন্য ক্যাটাগরি দেখুন অথবা <a href="#contact">যোগাযোগ করুন</a>।</p>
        @endif
    </div>
</section>
@if($featuredProducts->isNotEmpty())
<section class="hm-container hm-section" id="selected-products" aria-labelledby="selected-title">
    <div class="hm-section-heading"><div><h2 id="selected-title">নির্বাচিত পণ্য</h2><p>আরও প্যাক ও পণ্যের ধরন ঘুরে দেখুন।</p></div><a href="{{ url('/') }}#products">সব পণ্য</a></div>
    <div class="hm-product-grid">
        @foreach($featuredProducts as $previewProduct)
            @include('partials.storefront.product-card', ['product'=>$previewProduct, 'previewMode'=>$previewProduct->show_in_retail ? 'retail' : 'wholesale'])
        @endforeach
    </div>
</section>
@endif
