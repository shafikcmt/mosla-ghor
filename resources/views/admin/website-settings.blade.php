@extends('admin.layout')

@section('title', 'ওয়েবসাইট সেটিং')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-6">ওয়েবসাইট কন্টেন্ট সেটিং</h1>

{{-- ── Maintenance / notice mode (own form: saving it never touches the fields below) ── --}}
@php
    $mnErr   = $errors->getBag('maintenance');
    $mnOld   = fn ($key, $default = '') => old($key, $settings[$key] ?? $default);
    $mnOn    = \App\Support\Maintenance::enabled();
    $mnUntil = \App\Support\Maintenance::until();
    $mnMode  = $mnOld('maintenance_mode', 'full');
@endphp
<form id="maintenance" action="{{ route('admin.maintenance.update') }}" method="POST"
      class="mn-card bg-white rounded shadow-sm border border-gray-100 mb-6 {{ $mnOn ? 'is-on' : '' }}">
    @csrf
    <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between gap-3">
        <h2 class="text-sm font-semibold text-gray-600">মেইনটেন্যান্স / নোটিশ মোড</h2>
        @if($mnOn)
            <span class="mn-badge"><span class="mn-dot"></span>চালু আছে</span>
        @else
            <span class="text-xs text-green-700 bg-green-50 rounded-full px-3 py-1">বন্ধ — সাইট স্বাভাবিক</span>
        @endif
    </div>
    <div class="px-6 py-5 space-y-5">
        @if($mnErr->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3">
                @foreach($mnErr->all() as $error)<p>{{ $error }}</p>@endforeach
            </div>
        @endif
        @if($mnOn && \App\Support\Maintenance::expired())
            <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded px-4 py-3">
                ⚠️ শেষ সময় ({{ $mnUntil->format('d M Y, h:i A') }}) পেরিয়ে গেছে, কিন্তু মেইনটেন্যান্স এখনও চালু আছে।
                গ্রাহকদের কাউন্টডাউন লুকানো হয়েছে। কাজ শেষ হলে নিচ থেকে বন্ধ করুন বা নতুন সময় দিন।
            </div>
        @endif

        <input type="hidden" name="maintenance_enabled" value="0">
        <label class="mn-switch">
            <input type="checkbox" name="maintenance_enabled" value="1" @checked((string) $mnOld('maintenance_enabled', '0') === '1')>
            <span class="mn-track" aria-hidden="true"></span>
            <span>
                <span class="block text-sm font-medium text-gray-700">মেইনটেন্যান্স চালু করুন</span>
                <span class="block text-xs text-gray-400 mt-0.5">অ্যাডমিন প্যানেল সবসময় কাজ করবে। লগইন করা অ্যাডমিন আসল সাইট দেখবেন।</span>
            </span>
        </label>

        <div>
            <span class="block text-xs font-medium text-gray-600 mb-2">মোড</span>
            <div class="mn-modes">
                <label class="mn-mode">
                    <input type="radio" name="maintenance_mode" value="full" @checked($mnMode === 'full')>
                    <span class="text-sm font-medium text-gray-700">পুরো সাইট বন্ধ</span>
                    <small>গ্রাহক নোটিশ পেজ দেখবেন (HTTP 503)। অর্ডার ট্র্যাক ও ইনভয়েস দেখা যাবে।</small>
                </label>
                <label class="mn-mode">
                    <input type="radio" name="maintenance_mode" value="banner" @checked($mnMode === 'banner')>
                    <span class="text-sm font-medium text-gray-700">শুধু নোটিশ ব্যানার</span>
                    <small>সাইট স্বাভাবিক চলবে, উপরে একটি নোটিশ বার দেখাবে।</small>
                </label>
            </div>
        </div>

        <div class="space-y-3">
            <input type="hidden" name="maintenance_block_orders" value="0">
            <label class="flex items-start gap-3 cursor-pointer" data-mn-orders>
                <input type="checkbox" name="maintenance_block_orders" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked((string) $mnOld('maintenance_block_orders', '0') === '1')>
                <span>
                    <span class="block text-sm font-medium text-gray-700">নতুন অর্ডার ও চেকআউট বন্ধ রাখুন <span class="text-xs text-gray-400">(শুধু ব্যানার মোডে)</span></span>
                    <span class="block text-xs text-gray-400 mt-0.5">গ্রাহক “অর্ডার সাময়িকভাবে বন্ধ আছে” দেখবেন। পাইকারি enquiry চালু থাকবে।</span>
                </span>
            </label>
            <input type="hidden" name="maintenance_block_vendors" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="maintenance_block_vendors" value="1" class="mt-0.5 rounded border-gray-300"
                       @checked((string) $mnOld('maintenance_block_vendors', '0') === '1')>
                <span>
                    <span class="block text-sm font-medium text-gray-700">মার্চেন্ট প্যানেলও বন্ধ রাখুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">মার্চেন্ট লগইন কাজ করবে, কিন্তু প্যানেলে শুধু নোটিশ দেখবেন।</span>
                </span>
            </label>
        </div>

        <div class="mn-grid">
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="maintenance_title">শিরোনাম</label>
                <input type="text" id="maintenance_title" name="maintenance_title" maxlength="120"
                       value="{{ $mnOld('maintenance_title') }}" placeholder="{{ \App\Support\Maintenance::DEFAULT_TITLE }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="maintenance_until">আনুমানিক শেষ সময় <span class="text-gray-400">(বাংলাদেশ সময়, ঐচ্ছিক)</span></label>
                <input type="datetime-local" id="maintenance_until" name="maintenance_until"
                       value="{{ old('maintenance_until', $mnUntil?->format('Y-m-d\TH:i')) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">দিলে গ্রাহক কাউন্টডাউন দেখবেন। সময় পেরোলেও মেইনটেন্যান্স নিজে বন্ধ হবে না। Retry-After এই সময় থেকে হিসাব হয়।</p>
            </div>
            <div class="mn-wide">
                <label class="block text-xs font-medium text-gray-600 mb-1" for="maintenance_message">বার্তা</label>
                <textarea id="maintenance_message" name="maintenance_message" maxlength="1000" rows="3"
                          placeholder="{{ \App\Support\Maintenance::DEFAULT_MESSAGE }}"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-y">{{ $mnOld('maintenance_message') }}</textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="maintenance_contact">যোগাযোগ নম্বর (কল/WhatsApp, ঐচ্ছিক)</label>
                <input type="text" id="maintenance_contact" name="maintenance_contact" maxlength="30" inputmode="tel"
                       value="{{ $mnOld('maintenance_contact') }}" placeholder="01XXXXXXXXX"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="maintenance_allowed_ips">অনুমোদিত IP (ঐচ্ছিক)</label>
                <textarea id="maintenance_allowed_ips" name="maintenance_allowed_ips" rows="2" maxlength="1000"
                          placeholder="103.x.x.x, 2001:db8::1"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-gray-400 resize-y">{{ $mnOld('maintenance_allowed_ips') }}</textarea>
                <p class="text-gray-400 text-xs mt-1">এই IP থেকে সাইট স্বাভাবিক দেখাবে। আপনার বর্তমান IP: <span class="font-mono">{{ request()->ip() }}</span></p>
            </div>
        </div>

        <div class="mn-actions">
            <button type="submit" class="bg-gray-800 text-white px-8 py-2.5 rounded text-sm font-medium hover:bg-gray-700 transition-colors">মেইনটেন্যান্স সেটিং সংরক্ষণ</button>
            <a href="{{ route('admin.maintenance.preview') }}" target="_blank" rel="noopener" class="mn-btn mn-btn-outline">প্রিভিউ দেখুন ↗</a>
            <span class="text-xs text-gray-400">প্রিভিউ সংরক্ষিত লেখা দেখায় — আগে সংরক্ষণ করুন।</span>
        </div>
    </div>
</form>

<form action="{{ route('admin.website-settings.update') }}" method="POST">
    @csrf

    {{-- ── Site identity ──────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">সাইটের পরিচয়</h2>
        </div>
        <div class="px-6 py-5">
            <div class="max-w-md">
                <label class="block text-xs font-medium text-gray-600 mb-1" for="site_name">
                    সাইটের নাম <span class="text-red-500">*</span>
                </label>
                <input type="text" name="site_name" id="site_name" maxlength="100" required
                       value="{{ old('site_name', $settings['site_name'] ?? 'মসলা ঘর') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('site_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                <p class="text-gray-500 text-xs mt-2">ওয়েবসাইটের হেডার, ফুটার ও ব্রাউজার টাইটেলে এই নাম ব্যবহার হবে।</p>
                <label class="block text-xs font-medium text-gray-600 mt-4 mb-1" for="site_tagline">Website Tagline / স্লোগান</label>
                <input type="text" name="site_tagline" id="site_tagline" maxlength="100"
                       value="{{ old('site_tagline', $settings['site_tagline'] ?? 'Authentic Spice Store') }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('site_tagline')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    <section class="bg-white rounded shadow-sm border border-gray-100 mb-6 p-6">
        <h2 class="text-sm font-semibold text-gray-600 mb-4">SEO Settings</h2>
        <label for="meta_title" class="block text-sm mb-2">Default Meta Title</label>
        <input id="meta_title" name="meta_title" maxlength="70" value="{{ old('meta_title', $settings['meta_title'] ?? '') }}" class="w-full border rounded px-3 py-2">
        @error('meta_title')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        <p class="text-xs text-gray-500 mt-1 mb-4">ঐচ্ছিক, সর্বোচ্চ ৭০ অক্ষর। খালি থাকলে সাইটের নাম ব্যবহার হবে।</p>
        <label for="meta_description" class="block text-sm mb-2">Default Meta Description</label>
        <textarea id="meta_description" name="meta_description" maxlength="200" rows="3" class="w-full border rounded px-3 py-2">{{ old('meta_description', $settings['meta_description'] ?? '') }}</textarea>
        @error('meta_description')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
        <p class="text-xs text-gray-500 mt-1">ঐচ্ছিক, সর্বোচ্চ ২০০ অক্ষর। দোকানের সংক্ষিপ্ত পরিচয় লিখুন।</p>
    </section>
    <p class="mb-4 text-sm"><a class="underline" href="{{ route('admin.hero-slides.index') }}">Hero Slides পরিচালনা করুন</a> — সক্রিয় স্লাইড না থাকলে নিচের হিরো ব্যবহার হবে।</p>

    {{-- ── Hero section ────────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">হিরো সেকশন</h2>
        </div>
        <div class="px-6 py-5 space-y-5">

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="hero_badge_text">ব্যাজ টেক্সট</label>
                    <input type="text" name="hero_badge_text" id="hero_badge_text" maxlength="100"
                           value="{{ old('hero_badge_text', $settings['hero_badge_text'] ?? '') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-gray-400 text-xs mt-1">যেমন: খুচরা ও পাইকারি মসলা</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="hero_title">
                        শিরোনাম <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="hero_title" id="hero_title" maxlength="200" required
                           value="{{ old('hero_title', $settings['hero_title'] ?? '') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="hero_subtitle">সাবটাইটেল / বিবরণ</label>
                <textarea name="hero_subtitle" id="hero_subtitle" rows="3" maxlength="500"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400 resize-none">{{ old('hero_subtitle', $settings['hero_subtitle'] ?? '') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="primary_cta_text">প্রাইমারি বাটন</label>
                    <input type="text" name="primary_cta_text" id="primary_cta_text" maxlength="60"
                           value="{{ old('primary_cta_text', $settings['primary_cta_text'] ?? '') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-gray-400 text-xs mt-1">যেমন: পণ্য দেখুন</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="secondary_cta_text">সেকেন্ডারি বাটন</label>
                    <input type="text" name="secondary_cta_text" id="secondary_cta_text" maxlength="60"
                           value="{{ old('secondary_cta_text', $settings['secondary_cta_text'] ?? '') }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-gray-400 text-xs mt-1">যেমন: কম্বো দেখুন</p>
                </div>
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="hero_image_url">হিরো ব্যাকগ্রাউন্ড ছবির URL (ঐচ্ছিক)</label>
                <input type="text" name="hero_image_url" id="hero_image_url" maxlength="500"
                       value="{{ old('hero_image_url', $settings['hero_image_url'] ?? '') }}"
                       placeholder="https://..."
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>

        </div>
    </div>

    {{-- ── Contact & social ────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">যোগাযোগ ও সোশ্যাল</h2>
        </div>
        <div class="px-6 py-5 space-y-5">

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="whatsapp_number">WhatsApp / ফোন নম্বর</label>
                <input type="text" name="whatsapp_number" id="whatsapp_number" maxlength="20"
                       value="{{ old('whatsapp_number', $settings['whatsapp_number'] ?? '') }}"
                       placeholder="01700000000"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">সংখ্যা শুধু, যেমন: 01700000000</p>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="messenger_url">Messenger URL (ঐচ্ছিক)</label>
                    <input type="text" name="messenger_url" id="messenger_url" maxlength="300"
                           value="{{ old('messenger_url', $settings['messenger_url'] ?? '') }}"
                           placeholder="https://m.me/..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="facebook_page_url">Facebook Page URL (ঐচ্ছিক)</label>
                    <input type="text" name="facebook_page_url" id="facebook_page_url" maxlength="300"
                           value="{{ old('facebook_page_url', $settings['facebook_page_url'] ?? '') }}"
                           placeholder="https://facebook.com/..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
            </div>

        </div>
    </div>

    {{-- ── Footer ──────────────────────────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">ফুটার</h2>
        </div>
        <div class="px-6 py-5">
            <label class="block text-xs font-medium text-gray-600 mb-1" for="footer_text">কপিরাইট টেক্সট</label>
            <input type="text" name="footer_text" id="footer_text" maxlength="200"
                   value="{{ old('footer_text', $settings['footer_text'] ?? '') }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            <p class="text-gray-400 text-xs mt-1">সাইটের নাম ও সাল স্বয়ংক্রিয়ভাবে যুক্ত হয়।</p>
        </div>
    </div>

    {{-- ── Header announcement / marquee ──────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">Announcement Bar — হেডার ঘোষণা</h2>
        </div>
        <div class="px-6 py-5 space-y-5">

            <input type="hidden" name="announcement_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="announcement_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ (string) old('announcement_enabled', $settings['announcement_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">অ্যানাউন্সমেন্ট বার চালু রাখুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ থাকলে হেডারের উপরের ঘোষণা বারটি লুকানো থাকবে।</span>
                </span>
            </label>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_text_1">ঘোষণা টেক্সট (প্রধান)</label>
                <input type="text" name="announcement_text_1" id="announcement_text_1" maxlength="255"
                       value="{{ old('announcement_text_1', $settings['announcement_text_1'] ?? '') }}"
                       placeholder="আপনার ঘোষণা লিখুন"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">বাংলা/ইংরেজি দুটোই সাপোর্ট করে। বার চালু থাকলে এটি আবশ্যক।</p>
                @error('announcement_text_1')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_link_url">লিংক URL (ঐচ্ছিক)</label>
                    <input type="text" name="announcement_link_url" id="announcement_link_url" maxlength="300"
                           value="{{ old('announcement_link_url', $settings['announcement_link_url'] ?? '') }}"
                           placeholder="https://..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-gray-400 text-xs mt-1">/products বা https:// দিয়ে শুরু করুন। লেবেল খালি থাকলে ঘোষণাটি লিংক হবে।</p>
                    @error('announcement_link_url')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_link_label">লিংক লেবেল (ঐচ্ছিক)</label>
                    <input type="text" name="announcement_link_label" id="announcement_link_label" maxlength="60"
                           value="{{ old('announcement_link_label', $settings['announcement_link_label'] ?? '') }}"
                           placeholder="অর্ডার করুন"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
            </div>

        </div>
    </div>

    {{-- ── Vendor / merchant settings ─────────────────────────────── --}}
    <div class="bg-white rounded shadow-sm border border-gray-100 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-600">মার্চেন্ট সেটিং</h2>
        </div>
        <div class="px-6 py-5 space-y-4">

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="vendor_registration_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ ($settings['vendor_registration_enabled'] ?? '0') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">মার্চেন্ট রেজিস্ট্রেশন চালু রাখুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ থাকলে /vendor/register একটি বার্তা দেখাবে।</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="vendor_login_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ ($settings['vendor_login_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">মার্চেন্ট লগইন চালু রাখুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ থাকলে /vendor/login একটি বার্তা দেখাবে।</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="show_vendor_links_in_header" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ ($settings['show_vendor_links_in_header'] ?? '0') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">হেডারে মার্চেন্ট লিংক দেখান</span>
                    <span class="block text-xs text-gray-400 mt-0.5">হোম পেজের নেভিগেশনে মার্চেন্ট লিংক যুক্ত হবে।</span>
                </span>
            </label>

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="show_vendor_links_in_footer" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ ($settings['show_vendor_links_in_footer'] ?? '1') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">ফুটারে মার্চেন্ট লিংক দেখান</span>
                    <span class="block text-xs text-gray-400 mt-0.5">হোম পেজের ফুটারে মার্চেন্ট রেজিস্ট্রেশন/লগইন লিংক দেখাবে।</span>
                </span>
            </label>

        </div>
    </div>

    <div class="flex gap-3">
        <button type="submit"
                class="bg-gray-800 text-white px-8 py-2.5 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
            সংরক্ষণ করুন
        </button>
    </div>

</form>

@endsection
