{{-- Shared shell for the legal pages (privacy, terms, data deletion). Meta's app
     review links to these, so they stay plain, public and bilingual. --}}
@extends('storefront.layout')

@section('content')
@php
    $legalPhone = \App\Models\WebsiteSetting::get('phone');
    $legalWa    = preg_replace('/\D/', '', \App\Models\WebsiteSetting::get('whatsapp_number'));
    $legalFb    = \App\Models\WebsiteSetting::get('facebook_page_url');
@endphp

<nav class="text-xs text-gray-400 mb-5 flex items-center gap-1.5">
    <a href="/" class="hover:text-[#14532d]">হোম</a>
    <span>/</span>
    <span class="text-gray-700 font-medium">@yield('legal_title')</span>
</nav>

<div class="text-center mb-8">
    <h1 class="font-serif-bn text-[#14532d] text-3xl md:text-4xl font-bold">@yield('legal_title')</h1>
    <p class="text-gray-500 text-sm mt-2">@yield('legal_title_en') · Last updated: 12 October 2026</p>
</div>

<div class="max-w-3xl mx-auto bg-white rounded-2xl border border-amber-100 shadow-sm p-6 md:p-8 text-sm text-gray-700 leading-relaxed space-y-6 legal-body">
    @yield('legal_body')

    <section>
        <h2>যোগাযোগ / Contact</h2>
        <ul>
            <li>Website: <a href="{{ url('/') }}">{{ url('/') }}</a></li>
            @if($legalPhone)<li>Phone: <a href="tel:{{ $legalPhone }}">{{ $legalPhone }}</a></li>@endif
            @if($legalWa)<li>WhatsApp: <a href="https://wa.me/{{ $legalWa }}" rel="noopener">+{{ $legalWa }}</a></li>@endif
            @if($legalFb)<li>Facebook: <a href="{{ $legalFb }}" rel="noopener">{{ $legalFb }}</a></li>@endif
        </ul>
    </section>

    <p class="text-xs text-gray-400 pt-4 border-t border-amber-100">
        <a href="{{ route('legal.privacy') }}">Privacy Policy</a> ·
        <a href="{{ route('legal.terms') }}">Terms of Service</a> ·
        <a href="{{ route('legal.refund') }}">Refund Policy</a> ·
        <a href="{{ route('legal.delivery') }}">Delivery Policy</a> ·
        <a href="{{ route('legal.about') }}">About</a> ·
        <a href="{{ route('legal.data-deletion') }}">Data Deletion</a>
    </p>
</div>

<style>
    .legal-body h2 { font-weight: 700; color: #14532d; font-size: 1.05rem; margin-bottom: .5rem; }
    .legal-body p + p { margin-top: .5rem; }
    .legal-body ul { list-style: disc; padding-left: 1.25rem; }
    .legal-body ol { list-style: decimal; padding-left: 1.25rem; }
    .legal-body li + li { margin-top: .35rem; }
    .legal-body a { color: #14532d; text-decoration: underline; }
    .legal-body .en { color: #6b7280; }
</style>
@endsection
