@php $wholesalePreview = $products->where('show_in_wholesale', true)->take(8); @endphp
@php
    $wsWa = preg_replace('/\D+/', '', \App\Models\WebsiteSetting::get('whatsapp_number'));
    $wsWaIntl = $wsWa === '' ? '' : (str_starts_with($wsWa, '0') ? '88'.$wsWa : $wsWa);
@endphp
<section class="hm-wholesale" id="wholesale-products" aria-labelledby="wholesale-title">
    <div class="hm-container">
        {{-- B2B banner: why buy wholesale here + the two fastest ways to get a quote --}}
        <div class="hm-ws-banner">
            <div class="hm-ws-banner-copy">
                <span class="hm-ws-kicker">ব্যবসায়ীদের জন্য</span>
                <h2 id="wholesale-title">পাইকারি ও বাল্ক অর্ডার</h2>
                <p>দোকান, রেস্টুরেন্ট, ক্যাটারিং ও ডিস্ট্রিবিউটরদের জন্য পাইকারি দামে মসলা ও ড্রাই ফ্রুটস। পরিমাণ জানান — দ্রুত কোটেশন দেওয়া হবে।</p>
                <ul class="hm-ws-points">
                    <li>পরিমাণ অনুযায়ী পাইকারি দাম</li>
                    <li>চাহিদামতো প্যাকিং</li>
                    <li>নিয়মিত সাপ্লাই</li>
                </ul>
            </div>
            <div class="hm-ws-banner-cta">
                <a href="{{ $wholesaleHref }}" onclick="return msHeroClick(event, 'wholesale')" class="hm-ws-btn hm-ws-btn-light">সব পাইকারি পণ্য দেখুন</a>
                @if($wsWaIntl)
                <a href="https://wa.me/{{ $wsWaIntl }}?text={{ rawurlencode('আসসালামু আলাইকুম, পাইকারি দামে মসলা নিতে চাই। কোটেশন দিন।') }}" target="_blank" rel="noopener" class="hm-ws-btn hm-ws-btn-wa">WhatsApp-এ কোটেশন নিন</a>
                @endif
            </div>
        </div>
        @if($wholesalePreview->isNotEmpty())
        <div class="hm-ws-grid">
            @foreach($wholesalePreview as $product)
                @include('partials.storefront.wholesale-tile')
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
