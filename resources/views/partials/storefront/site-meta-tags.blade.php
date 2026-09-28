{{-- Admin-managed verification/meta tags (Admin → সাইট ভেরিফিকেশন ও মেটা ট্যাগ). Built here from
     validated fields with both values escaped; storefront shells + maintenance notice only. --}}
@foreach(\App\Models\SiteMetaTag::forHead() as $smt)
    <meta {{ $smt['attribute'] === 'property' ? 'property' : 'name' }}="{{ $smt['name'] }}" content="{{ $smt['content'] }}">
@endforeach
