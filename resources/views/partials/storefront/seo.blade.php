@php
    // 'seo_title' section = a complete custom title (site name not appended again).
    $seoTitle = trim($fullTitle ?? '') !== ''
        ? $fullTitle
        : (trim($pageTitle ?? '') !== ''
            ? $pageTitle.' — '.$siteName
            : (trim(\App\Models\WebsiteSetting::get('meta_title')) ?: $siteName.' — '.($siteTagline ?: 'খুচরা ও পাইকারি মসলা')));
    $seoDescription = trim($pageDescription ?? '')
        ?: (trim(\App\Models\WebsiteSetting::get('meta_description')) ?: $siteName.' — খুচরা প্যাক ও পাইকারি মসলা দেখুন এবং আপনার প্রয়োজন অনুযায়ী অর্ডার করুন।');
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">

@if(trim($pageTitle ?? '') === '' && trim($fullTitle ?? '') === '')
@php
    $seoWs     = $ws ?? \App\Models\WebsiteSetting::allKeyed();
    $seoUrl    = url('/').'/';
    $seoImage  = \App\Support\ProductMedia::url($seoWs['og_image_url'] ?? ($seoWs['hero_image_url'] ?? null)) ?: asset('icons/icon-512.png');
    $seoPhone  = preg_replace('/\D/', '', $seoWs['whatsapp_number'] ?? '');
    $seoSame   = array_values(array_filter([$seoWs['facebook_page_url'] ?? null]));
    $seoLd = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            array_filter([
                '@type'     => 'Store',
                '@id'       => $seoUrl.'#store',
                'name'      => $siteName,
                'url'       => $seoUrl,
                'logo'      => asset('icons/icon-512.png'),
                'image'     => $seoImage,
                'description' => $seoDescription,
                'telephone' => $seoPhone ? '+88'.$seoPhone : null,
                'areaServed' => 'BD',
                'priceRange' => '৳',
                'sameAs'    => $seoSame ?: null,
            ]),
            [
                '@type' => 'WebSite',
                '@id'   => $seoUrl.'#website',
                'url'   => $seoUrl,
                'name'  => $siteName,
                'inLanguage' => 'bn-BD',
            ],
        ],
    ];
@endphp
<link rel="canonical" href="{{ $seoUrl }}">
<meta name="robots" content="index, follow, max-image-preview:large">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ $siteName }}">
<meta property="og:locale" content="bn_BD">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ $seoDescription }}">
<meta name="twitter:image" content="{{ $seoImage }}">
<script type="application/ld+json">{!! json_encode($seoLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) !!}</script>
@endif
