@extends('admin.layout')
@section('title', 'লগইন ও রেজিস্ট্রেশন সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">লগইন ও রেজিস্ট্রেশন সেটিং</h1>
</div>

@if(session('success'))
<div class="mb-5 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded text-sm">{{ session('success') }}</div>
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
                    <p class="text-sm font-medium text-gray-800">মার্চেন্ট রেজিস্ট্রেশন চালু</p>
                    <p class="text-xs text-gray-400 mt-0.5">/vendor/register পেজ ফর্ম দেখাবে</p>
                </div>
                <input type="checkbox" name="vendor_registration_enabled"
                       {{ $settings['vendor_registration_enabled'] === '1' ? 'checked' : '' }}
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

    <div class="bg-white rounded shadow px-6 py-5">
        <button type="submit"
                class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
            সেটিং সংরক্ষণ করুন
        </button>
    </div>

</form>
@endsection
