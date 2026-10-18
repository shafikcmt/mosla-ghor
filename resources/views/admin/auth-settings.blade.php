@extends('admin.layout')
@section('title', 'লগইন ও রেজিস্ট্রেশন সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">লগইন ও রেজিস্ট্রেশন সেটিং</h1>
</div>

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
@endif

@if($errors->any())
<div class="mb-5 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded text-sm">
    <ul class="list-disc list-inside space-y-0.5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ route('admin.auth-settings.update') }}" method="POST">
    @csrf

    {{-- Customer Section --}}
    <div class="bg-white rounded shadow mb-5">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">কাস্টমার / ইউজার</h3>
        </div>
        <div class="px-6 py-5 space-y-4">

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">কাস্টমার লগইন চালু</p>
                    <p class="text-xs text-gray-400 mt-0.5">/login পেজ অ্যাক্সেসযোগ্য থাকবে</p>
                </div>
                <input type="checkbox" name="customer_login_enabled"
                       {{ $settings['customer_login_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">পাসওয়ার্ড দিয়ে লগইন</p>
                    <p class="text-xs text-gray-400 mt-0.5">ফোন/ইমেইল + পাসওয়ার্ড লগইন চালু রাখবে</p>
                </div>
                <input type="checkbox" name="customer_password_login"
                       {{ $settings['customer_password_login'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">OTP দিয়ে লগইন</p>
                    <p class="text-xs text-gray-400 mt-0.5">পাসওয়ার্ড ছাড়া কোড দিয়ে লগইন (নিচের চ্যানেল লাগবে)</p>
                </div>
                <input type="checkbox" name="customer_otp_login"
                       {{ $settings['customer_otp_login'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">কাস্টমার রেজিস্ট্রেশন চালু</p>
                    <p class="text-xs text-gray-400 mt-0.5">/register পেজ অ্যাক্সেসযোগ্য থাকবে</p>
                </div>
                <input type="checkbox" name="customer_registration_enabled"
                       {{ $settings['customer_registration_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">হেডারে কাস্টমার লগইন/রেজিস্ট্রেশন বাটন দেখাও</p>
                    <p class="text-xs text-gray-400 mt-0.5">মূল নেভবারে লগইন ও রেজিস্ট্রেশন বাটন</p>
                </div>
                <input type="checkbox" name="show_customer_links_in_header"
                       {{ $settings['show_customer_links_in_header'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3">
                <div>
                    <p class="text-sm font-medium text-gray-800">ফুটারে কাস্টমার লিংক দেখাও</p>
                    <p class="text-xs text-gray-400 mt-0.5">ফুটারে লগইন ও রেজিস্ট্রেশন লিংক</p>
                </div>
                <input type="checkbox" name="show_customer_links_in_footer"
                       {{ $settings['show_customer_links_in_footer'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

        </div>
    </div>

    {{-- Guest (not logged in) permissions --}}
    @php
        $gcOff    = ($settings['guest_checkout_enabled'] ?? '1') !== '1';
        $regOff   = ($settings['customer_registration_enabled'] ?? '1') !== '1';
    @endphp
    <div class="bg-white rounded shadow mb-5" id="guest-access">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">গেস্ট (লগইন ছাড়া) অনুমতি</h3>
            <p class="text-xs text-gray-400 mt-1">বন্ধ করলে লগইন ছাড়া এই কাজগুলো করা যাবে না। লগইন করা সবাই আগের মতোই পারবেন।</p>
        </div>
        <div class="px-6 py-5 space-y-4">
            @if($gcOff && $regOff)
                <div class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded px-4 py-3">
                    ⚠️ গেস্ট চেকআউট ও কাস্টমার রেজিস্ট্রেশন দুটোই বন্ধ — নতুন কাস্টমার অর্ডার করতে পারবে না। শুধু আগের অ্যাকাউন্টধারীরা লগইন করে অর্ডার করতে পারবেন।
                </div>
            @endif

            @foreach([
                'guest_checkout_enabled' => ['গেস্ট চেকআউট', 'লগইন ছাড়া অর্ডার করা যাবে। বন্ধ করলে কার্টে "অর্ডার করতে লগইন করুন" দেখাবে; কার্ট সংরক্ষিত থাকবে। বন্ধ করতে কাস্টমার লগইন চালু থাকতে হবে।'],
                'guest_enquiry_enabled'  => ['গেস্ট Enquiry', 'লগইন ছাড়া পাইকারি ও কম্বো enquiry পাঠানো যাবে।'],
                'guest_review_enabled'   => ['গেস্ট রিভিউ', 'লগইন ছাড়া পণ্যের রিভিউ দেওয়া যাবে।'],
            ] as $key => [$label, $help])
            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">{{ $label }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $help }}</p>
                </div>
                <input type="checkbox" name="{{ $key }}"
                       {{ ($settings[$key] ?? '1') === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>
            @endforeach
        </div>
    </div>

    {{-- Vendor Section --}}
    <div class="bg-white rounded shadow mb-5">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">মার্চেন্ট / ভেন্ডর</h3>
        </div>
        <div class="px-6 py-5 space-y-4">

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">মার্চেন্ট লগইন চালু</p>
                    <p class="text-xs text-gray-400 mt-0.5">/vendor/login পেজ অ্যাক্সেসযোগ্য থাকবে</p>
                </div>
                <input type="checkbox" name="vendor_login_enabled"
                       {{ $settings['vendor_login_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">পাসওয়ার্ড দিয়ে লগইন</p>
                    <p class="text-xs text-gray-400 mt-0.5">ফোন/ইমেইল + পাসওয়ার্ড লগইন চালু রাখবে</p>
                </div>
                <input type="checkbox" name="vendor_password_login"
                       {{ $settings['vendor_password_login'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">OTP দিয়ে লগইন</p>
                    <p class="text-xs text-gray-400 mt-0.5">পাসওয়ার্ড ছাড়া কোড দিয়ে লগইন (নিচের চ্যানেল লাগবে)</p>
                </div>
                <input type="checkbox" name="vendor_otp_login"
                       {{ $settings['vendor_otp_login'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">মার্চেন্ট রেজিস্ট্রেশন চালু</p>
                    <p class="text-xs text-gray-400 mt-0.5">/vendor/register পেজ ফর্ম দেখাবে</p>
                </div>
                <input type="checkbox" name="vendor_registration_enabled"
                       {{ $settings['vendor_registration_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">রেজিস্ট্রেশনের পর স্বয়ংক্রিয় অনুমোদন</p>
                    <p class="text-xs text-gray-400 mt-0.5">চালু থাকলে নতুন মার্চেন্ট সাথে সাথেই approved হবে</p>
                </div>
                <input type="checkbox" name="vendor_auto_approve"
                       {{ $settings['vendor_auto_approve'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">হেডারে মার্চেন্ট লিংক দেখাও</p>
                    <p class="text-xs text-gray-400 mt-0.5">মূল নেভবারে "মার্চেন্ট হন" ও "মার্চেন্ট লগইন"</p>
                </div>
                <input type="checkbox" name="show_vendor_links_in_header"
                       {{ $settings['show_vendor_links_in_header'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">ফুটারে মার্চেন্ট লিংক দেখাও</p>
                    <p class="text-xs text-gray-400 mt-0.5">ফুটারে মার্চেন্ট রেজিস্ট্রেশন ও লগইন লিংক</p>
                </div>
                <input type="checkbox" name="show_vendor_links_in_footer"
                       {{ $settings['show_vendor_links_in_footer'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <div class="py-3">
                <label class="block text-sm font-medium text-gray-800 mb-1.5">
                    মার্চেন্ট রেজিস্ট্রেশন বন্ধ থাকলে বার্তা
                </label>
                <p class="text-xs text-gray-400 mb-2">/vendor/register ভিজিট করলে এই বার্তা দেখাবে</p>
                <textarea name="vendor_registration_message" rows="3"
                          class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">{{ $settings['vendor_registration_message'] }}</textarea>
            </div>

        </div>
    </div>

    {{-- OTP / Mobile Section --}}
    <div class="bg-white rounded shadow mb-5">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">OTP / মোবাইল সেটিং</h3>
            <p class="text-xs text-gray-400 mt-1">OTP লগইন কাজ করতে কমপক্ষে একটি চ্যানেল চালু থাকতে হবে। SMS / WhatsApp গেটওয়ের key .env-এ সেট করুন।</p>
        </div>
        <div class="px-6 py-5 space-y-4">

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">SMS চ্যানেল</p>
                    <p class="text-xs text-gray-400 mt-0.5">SMS গেটওয়ে দিয়ে OTP পাঠানো</p>
                </div>
                <input type="checkbox" name="otp_sms_enabled"
                       {{ $settings['otp_sms_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">WhatsApp চ্যানেল</p>
                    <p class="text-xs text-gray-400 mt-0.5">WhatsApp API দিয়ে OTP পাঠানো</p>
                </div>
                <input type="checkbox" name="otp_whatsapp_enabled"
                       {{ $settings['otp_whatsapp_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">ইমেইল চ্যানেল</p>
                    <p class="text-xs text-gray-400 mt-0.5">ইমেইলে OTP পাঠানো</p>
                </div>
                <input type="checkbox" name="otp_email_enabled"
                       {{ $settings['otp_email_enabled'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <label class="flex items-center justify-between py-3 border-b border-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-800">রেজিস্ট্রেশনে ইমেইল ফিল্ড দেখাও</p>
                    <p class="text-xs text-gray-400 mt-0.5">কাস্টমার রেজিস্ট্রেশন ফর্মে ইমেইল ইনপুট</p>
                </div>
                <input type="checkbox" name="show_email_field_register"
                       {{ $settings['show_email_field_register'] === '1' ? 'checked' : '' }}
                       class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
            </label>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">OTP মেয়াদ (মিনিট)</label>
                    <input type="number" name="otp_expiry_minutes" min="1" max="60" value="{{ $settings['otp_expiry_minutes'] }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">পুনরায় পাঠানোর বিরতি (সেকেন্ড)</label>
                    <input type="number" name="otp_resend_cooldown_seconds" min="0" max="600" value="{{ $settings['otp_resend_cooldown_seconds'] }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-800 mb-1.5">সর্বোচ্চ চেষ্টা</label>
                    <input type="number" name="otp_max_attempts" min="1" max="10" value="{{ $settings['otp_max_attempts'] }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                </div>
            </div>

        </div>
    </div>

    {{-- Social login --}}
    <div class="bg-white rounded shadow mb-5" id="social-login">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">সোশ্যাল লগইন ও রেজিস্ট্রেশন (Google / Facebook)</h3>
            <p class="text-xs text-gray-500 mt-1">কাস্টমার এক ক্লিকে Google বা Facebook দিয়ে অ্যাকাউন্ট খুলতে ও লগইন করতে পারবে — লগইন, রেজিস্ট্রেশন ও চেকআউটের লগইন পেজে বাটন দেখাবে। প্রথমবার শুধু মোবাইল নম্বর চাওয়া হবে। নিচে Key দিন, চালু করুন ও সংরক্ষণ করুন।</p>
        </div>
        <div class="px-6 py-5 space-y-6">
            @foreach(\App\Support\AuthSettings::SOCIAL_PROVIDERS as $provider => $label)
            @php
                $source = \App\Support\AuthSettings::socialKeySource($provider);
                $live = \App\Support\AuthSettings::socialEnabled($provider);
                $hasSecret = filled(\App\Models\WebsiteSetting::get("social_{$provider}_client_secret"));
                $callback = url('/auth/'.$provider.'/callback');
            @endphp
            <div class="rounded-lg border {{ $live ? 'border-green-200' : 'border-gray-200' }}">
                <label class="flex items-center justify-between gap-4 px-4 py-3 {{ $live ? 'bg-green-50' : 'bg-gray-50' }} rounded-t-lg cursor-pointer">
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ $label }} দিয়ে লগইন / রেজিস্ট্রেশন</p>
                        @if($live)
                            <p class="text-xs text-green-700 mt-0.5">✓ চালু আছে — কাস্টমাররা বাটন দেখছে</p>
                        @elseif($source)
                            <p class="text-xs text-amber-700 mt-0.5">Key সেট আছে — চালু করতে ডানের বক্সে টিক দিয়ে সংরক্ষণ করুন</p>
                        @else
                            <p class="text-xs text-amber-700 mt-0.5">Key দেওয়া নেই — নিচে Client ID ও Secret দিন। ততক্ষণ বাটন দেখাবে না।</p>
                        @endif
                    </div>
                    <input type="checkbox" name="customer_{{ $provider }}_login"
                           {{ ($settings['customer_'.$provider.'_login'] ?? '1') === '1' ? 'checked' : '' }}
                           class="w-5 h-5 rounded text-[#14532d] border-gray-300 focus:ring-[#14532d]">
                </label>
                <div class="px-4 py-4 space-y-3">
                    @if($source === 'env')
                        <p class="text-xs text-gray-500">Key সার্ভারের .env ফাইল থেকে নেওয়া হচ্ছে। এখানে দেওয়ার দরকার নেই।</p>
                    @else
                    <div class="grid sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1" for="sg-{{ $provider }}-id">{{ $provider === 'facebook' ? 'App ID' : 'Client ID' }}</label>
                            <input id="sg-{{ $provider }}-id" name="social_{{ $provider }}_client_id" maxlength="255" autocomplete="off"
                                   value="{{ old('social_'.$provider.'_client_id', \App\Models\WebsiteSetting::get('social_'.$provider.'_client_id')) }}"
                                   placeholder="{{ $provider === 'google' ? '1234…apps.googleusercontent.com' : '123456789012345' }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1" for="sg-{{ $provider }}-secret">{{ $provider === 'facebook' ? 'App Secret' : 'Client Secret' }}</label>
                            <input id="sg-{{ $provider }}-secret" type="password" name="social_{{ $provider }}_client_secret" maxlength="255" autocomplete="new-password"
                                   placeholder="{{ $hasSecret ? '•••••••• সংরক্ষিত — বদলাতে নতুনটি দিন' : 'Secret পেস্ট করুন' }}"
                                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                            @if($hasSecret)
                            <label class="inline-flex items-center gap-1.5 mt-1 text-[11px] text-gray-500"><input type="checkbox" name="social_{{ $provider }}_clear_secret" value="1" class="rounded border-gray-300"> সংরক্ষিত Secret মুছুন</label>
                            @endif
                        </div>
                    </div>
                    <p class="text-[11px] text-gray-400">Secret এনক্রিপ্ট করে রাখা হয় এবং আর কখনো দেখানো হয় না।</p>
                    @endif

                    <div>
                        <p class="text-xs font-medium text-gray-600 mb-1">{{ $provider === 'google' ? 'Authorized redirect URI' : 'Valid OAuth Redirect URI' }} (কপি করে {{ $label }}-এ দিন)</p>
                        <div class="flex gap-2">
                            <input readonly value="{{ $callback }}" onclick="this.select()" class="flex-1 min-w-0 border border-gray-200 bg-gray-50 rounded px-3 py-2 text-xs font-mono">
                            <button type="button" onclick="navigator.clipboard && navigator.clipboard.writeText('{{ $callback }}'); this.textContent='✓ কপি হয়েছে'" class="shrink-0 border border-gray-300 rounded px-3 text-xs hover:bg-gray-50">কপি</button>
                        </div>
                    </div>

                    <details class="text-xs text-gray-600">
                        <summary class="cursor-pointer font-semibold text-[#14532d]">কীভাবে {{ $label }} Key পাবেন? (ধাপে ধাপে)</summary>
                        @if($provider === 'google')
                        <ol class="list-decimal pl-5 mt-2 space-y-1">
                            <li><a href="https://console.cloud.google.com/apis/credentials" target="_blank" rel="noopener" class="text-blue-600 underline">Google Cloud Console → Credentials</a> খুলুন (প্রয়োজনে নতুন Project তৈরি করুন)।</li>
                            <li><b>OAuth consent screen</b>: User type <b>External</b>, অ্যাপের নাম, সাপোর্ট ইমেইল দিন → সংরক্ষণ করে <b>Publish app</b> করুন।</li>
                            <li><b>Create credentials → OAuth client ID</b> → Application type: <b>Web application</b>।</li>
                            <li><b>Authorized redirect URIs</b>-এ উপরের লিংকটি দিন → Create।</li>
                            <li>যে <b>Client ID</b> ও <b>Client Secret</b> দেখাবে, এখানে পেস্ট করুন → উপরের বক্সে টিক → সংরক্ষণ।</li>
                        </ol>
                        @else
                        <ol class="list-decimal pl-5 mt-2 space-y-1">
                            <li><a href="https://developers.facebook.com/apps/" target="_blank" rel="noopener" class="text-blue-600 underline">Meta for Developers → My Apps</a> → <b>Create App</b> → use case: <b>Authenticate and request data from users with Facebook Login</b>।</li>
                            <li><b>Facebook Login → Settings</b>-এ <b>Valid OAuth Redirect URIs</b>-এ উপরের লিংকটি দিন → Save।</li>
                            <li><b>Use cases → Customize</b>-এ <b>email</b> permission যোগ করুন।</li>
                            <li><b>App settings → Basic</b>: Privacy Policy URL ও App Domain ({{ request()->getHost() }}) দিন, তারপর অ্যাপটি <b>Live</b> করুন।</li>
                            <li>ওই পেজের <b>App ID</b> ও <b>App Secret</b> এখানে পেস্ট করুন → উপরের বক্সে টিক → সংরক্ষণ।</li>
                        </ol>
                        @endif
                    </details>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="bg-white rounded shadow px-6 py-5">
        <button type="submit"
                class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
            সেটিং সংরক্ষণ করুন
        </button>
    </div>

</form>
@endsection
