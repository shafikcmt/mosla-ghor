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

    <div class="bg-white rounded shadow px-6 py-5">
        <button type="submit"
                class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
            সেটিং সংরক্ষণ করুন
        </button>
    </div>

</form>
@endsection
