@extends('admin.layout')

@section('title', 'ওয়েবসাইট সেটিং')

@section('content')

<h1 class="text-xl font-bold text-gray-800 mb-6">ওয়েবসাইট কন্টেন্ট সেটিং</h1>

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
            </div>
        </div>
    </div>

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
                    <p class="text-gray-400 text-xs mt-1">যেমন: ঈদ স্পেশাল কালেকশন</p>
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
            <h2 class="text-sm font-semibold text-gray-600">হেডার ঘোষণা / মার্কি বার</h2>
        </div>
        <div class="px-6 py-5 space-y-5">

            <label class="flex items-start gap-3 cursor-pointer">
                <input type="checkbox" name="announcement_enabled" value="1" class="mt-0.5 rounded border-gray-300 text-[#14532d]"
                    {{ ($settings['announcement_enabled'] ?? '1') === '1' ? 'checked' : '' }}>
                <span>
                    <span class="block text-sm font-medium text-gray-700">অ্যানাউন্সমেন্ট বার চালু রাখুন</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ থাকলে হেডারের উপরের চলমান বারটি লুকানো থাকবে।</span>
                </span>
            </label>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_text_1">ঘোষণা টেক্সট (প্রধান)</label>
                <input type="text" name="announcement_text_1" id="announcement_text_1" maxlength="255"
                       value="{{ old('announcement_text_1', $settings['announcement_text_1'] ?? '') }}"
                       placeholder="ঈদ স্পেশাল — এখনই অর্ডার করুন এবং পান বিশেষ ছাড়!"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                <p class="text-gray-400 text-xs mt-1">বাংলা/ইংরেজি দুটোই সাপোর্ট করে। বার চালু থাকলে এটি আবশ্যক।</p>
                @error('announcement_text_1')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_text_2">দ্বিতীয় টেক্সট (ঐচ্ছিক)</label>
                <input type="text" name="announcement_text_2" id="announcement_text_2" maxlength="255"
                       value="{{ old('announcement_text_2', $settings['announcement_text_2'] ?? '') }}"
                       placeholder="১০০% খাঁটি মসলা — সারা বাংলাদেশে হোম ডেলিভারি"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_link_url">লিংক URL (ঐচ্ছিক)</label>
                    <input type="text" name="announcement_link_url" id="announcement_link_url" maxlength="300"
                           value="{{ old('announcement_link_url', $settings['announcement_link_url'] ?? '') }}"
                           placeholder="https://..."
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-gray-400 text-xs mt-1">দিলে পুরো বারটি ক্লিকেবল হবে।</p>
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

            <div class="grid grid-cols-3 gap-5">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_bg_color">ব্যাকগ্রাউন্ড কালার</label>
                    <div class="flex items-center gap-2">
                        <input type="color" value="{{ old('announcement_bg_color', $settings['announcement_bg_color'] ?? '#C9A227') }}"
                               onchange="document.getElementById('announcement_bg_color').value = this.value"
                               class="h-9 w-10 border border-gray-300 rounded cursor-pointer p-0.5">
                        <input type="text" name="announcement_bg_color" id="announcement_bg_color" maxlength="20"
                               value="{{ old('announcement_bg_color', $settings['announcement_bg_color'] ?? '') }}"
                               placeholder="#C9A227"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <p class="text-gray-400 text-xs mt-1">খালি রাখলে ডিফল্ট স্বর্ণালি।</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_text_color">টেক্সট কালার</label>
                    <div class="flex items-center gap-2">
                        <input type="color" value="{{ old('announcement_text_color', $settings['announcement_text_color'] ?? '#064E2E') }}"
                               onchange="document.getElementById('announcement_text_color').value = this.value"
                               class="h-9 w-10 border border-gray-300 rounded cursor-pointer p-0.5">
                        <input type="text" name="announcement_text_color" id="announcement_text_color" maxlength="20"
                               value="{{ old('announcement_text_color', $settings['announcement_text_color'] ?? '') }}"
                               placeholder="#064E2E"
                               class="flex-1 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    </div>
                    <p class="text-gray-400 text-xs mt-1">খালি রাখলে ডিফল্ট গাঢ় সবুজ।</p>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1" for="announcement_speed">স্ক্রল গতি</label>
                    <select name="announcement_speed" id="announcement_speed"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                        @php($annSpeed = old('announcement_speed', $settings['announcement_speed'] ?? 'normal'))
                        <option value="slow"   {{ $annSpeed === 'slow' ? 'selected' : '' }}>ধীর</option>
                        <option value="normal" {{ $annSpeed === 'normal' ? 'selected' : '' }}>স্বাভাবিক</option>
                        <option value="fast"   {{ $annSpeed === 'fast' ? 'selected' : '' }}>দ্রুত</option>
                    </select>
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
