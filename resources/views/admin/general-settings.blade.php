@extends('admin.layout')

@section('title', 'জেনারেল সেটিং')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">জেনারেল সেটিং</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.general-settings.update') }}" method="POST">
        @csrf

        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">অর্ডার সেটিং</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="minimum_order_amount">
                        ন্যূনতম অর্ডার পরিমাণ (৳)
                        <span class="text-gray-400 font-normal">(গ্রাহকের কার্টের মোট এর চেয়ে কম হলে অর্ডার দেওয়া যাবে না)</span>
                    </label>
                    <input type="number" name="minimum_order_amount" id="minimum_order_amount"
                           value="{{ old('minimum_order_amount', $settings->minimum_order_amount) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('minimum_order_amount')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="default_packaging_cost">
                        প্যাকেজিং চার্জ (৳)
                        <span class="text-gray-400 font-normal">(প্রতিটি অর্ডারে যোগ হয়)</span>
                    </label>
                    <input type="number" name="default_packaging_cost" id="default_packaging_cost"
                           value="{{ old('default_packaging_cost', $settings->default_packaging_cost) }}"
                           min="0" step="1"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('default_packaging_cost')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

            </div>
        </div>

        <div class="px-6 py-5">
            <button type="submit"
                    class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">
                সেটিং সংরক্ষণ করুন
            </button>
        </div>

    </form>
</div>

{{-- ── Image compression (own form) ─────────────────────────────────── --}}
@php
    $imgErr = $errors->getBag('images');
    $imgOn = old('image_optimize_enabled', \App\Models\WebsiteSetting::get('image_optimize_enabled', '1')) === '1';
    $webp = \App\Support\ImageOptimizer::webpSupported();
@endphp
<div class="bg-white rounded shadow mt-6" id="image-settings">
    <form action="{{ route('admin.general-settings.images') }}" method="POST">
        @csrf
        <div class="px-6 py-5 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-1">ছবি কম্প্রেশন</h3>
            <p class="text-xs text-gray-400 mb-4">আপলোডের সময় ছবি নিজে থেকেই ছোট ও হালকা হয় (ব্রাউজারে ও সার্ভারে) — TinyPNG লাগবে না। KYC ডকুমেন্ট কখনো বদলানো হয় না।</p>

            @if($imgErr->any())
                <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded px-4 py-3 mb-4">
                    @foreach($imgErr->all() as $e)<p>{{ $e }}</p>@endforeach
                </div>
            @endif

            <input type="hidden" name="image_optimize_enabled" value="0">
            <label class="flex items-start gap-3 cursor-pointer mb-5">
                <input type="checkbox" name="image_optimize_enabled" value="1" class="mt-0.5 rounded border-gray-300" @checked($imgOn)>
                <span>
                    <span class="block text-sm font-medium text-gray-700">অটো কম্প্রেশন চালু</span>
                    <span class="block text-xs text-gray-400 mt-0.5">বন্ধ করলে ছবি আগের মতো হুবহু সংরক্ষণ হবে।</span>
                </span>
            </label>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="image_max_product_px">পণ্যের ছবির সর্বোচ্চ মাপ (px, লম্বা দিক)</label>
                    <input type="number" name="image_max_product_px" id="image_max_product_px" min="600" max="4000" step="100"
                           value="{{ old('image_max_product_px', \App\Models\WebsiteSetting::get('image_max_product_px', '1600')) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-xs text-gray-400 mt-1">প্রস্তাবিত ১৬০০। ছোট ছবি কখনো বড় করা হয় না।</p>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5" for="image_quality">কোয়ালিটি (৪০–৯৫)</label>
                    <input type="number" name="image_quality" id="image_quality" min="40" max="95" step="1"
                           value="{{ old('image_quality', \App\Models\WebsiteSetting::get('image_quality', '80')) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <p class="text-xs text-gray-400 mt-1">প্রস্তাবিত ৮০ — পণ্য, হিরো ও ব্যানার ছবিতে প্রযোজ্য। লোগো, পেমেন্ট স্ক্রিনশট, রিভিউ ও শেয়ার ছবির মান নির্দিষ্ট।</p>
                </div>
            </div>

            @php $imgLib = \App\Support\ImageOptimizer::libraryInstalled(); @endphp
            <p class="text-xs mt-4 {{ $imgLib ? 'text-green-700' : 'text-red-600' }}" data-image-library="{{ $imgLib ? 'yes' : 'no' }}">
                Image library: {{ $imgLib ? '✅ ইনস্টল আছে' : '❌ নেই — ব্রাউজারে ছোট করা চালু আছে' }}
            </p>
            <p class="text-xs mt-1 {{ $webp ? 'text-green-700' : 'text-gray-500' }}">
                সার্ভার WebP: {{ $webp ? 'সমর্থিত' : 'নেই — সার্ভার JPEG/PNG ব্যবহার করবে; ব্রাউজার থেকে WebP আপলোড আগের মতোই কাজ করবে' }}
            </p>
        </div>
        <div class="px-6 py-5">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-gray-700 transition-colors">ছবির সেটিং সংরক্ষণ</button>
        </div>
    </form>
</div>

@endsection
