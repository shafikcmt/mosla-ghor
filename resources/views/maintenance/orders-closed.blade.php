@extends('storefront.layout')
@section('title', 'অর্ডার সাময়িকভাবে বন্ধ')
@php /* no tracking on maintenance pages */ \App\Support\MetaPixel::disableForPage(); @endphp

@section('head')
    <meta name="robots" content="noindex, nofollow">
@endsection

@section('content')
@php $links = \App\Support\Maintenance::contactLinks(); @endphp
<div class="max-w-lg mx-auto my-8 bg-white rounded-2xl shadow-sm border border-amber-200 p-6 text-center">
    <div class="text-4xl mb-3" aria-hidden="true">🛒</div>
    <h1 class="text-xl font-bold text-[#14532d] mb-2">অর্ডার সাময়িকভাবে বন্ধ আছে</h1>
    <p class="text-gray-600 leading-relaxed whitespace-pre-line">{{ \App\Support\Maintenance::message() }}</p>
    <p class="text-sm text-gray-500 mt-3">আপনার কার্টের পণ্য সংরক্ষিত আছে — অর্ডার চালু হলে আবার চেষ্টা করুন।</p>
    <div class="flex flex-wrap gap-3 justify-center mt-6">
        <a href="{{ url('/') }}" class="btn-gold text-[#0f3d22] font-semibold px-5 py-2.5 rounded-xl">পণ্য দেখুন</a>
        @if($links)
            <a href="{{ $links['whatsapp'] }}" target="_blank" rel="noopener" class="bg-green-600 text-white font-semibold px-5 py-2.5 rounded-xl">WhatsApp</a>
        @endif
    </div>
</div>
@endsection
