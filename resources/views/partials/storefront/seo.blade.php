@php
    $seoTitle = trim($pageTitle ?? '') !== ''
        ? $pageTitle.' — '.$siteName
        : (trim(\App\Models\WebsiteSetting::get('meta_title')) ?: $siteName.' — '.($siteTagline ?: 'খুচরা ও পাইকারি মসলা'));
    $seoDescription = trim($pageDescription ?? '')
        ?: (trim(\App\Models\WebsiteSetting::get('meta_description')) ?: $siteName.' — খুচরা প্যাক ও পাইকারি মসলা দেখুন এবং আপনার প্রয়োজন অনুযায়ী অর্ডার করুন।');
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
