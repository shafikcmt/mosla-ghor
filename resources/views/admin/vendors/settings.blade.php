@extends('admin.layout')
@section('title', 'মাল্টিভেন্ডর সেটিং')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.vendors.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ভেন্ডর তালিকা</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">মাল্টিভেন্ডর সেটিং</h1>
</div>

<div class="bg-white rounded-xl border border-gray-100 p-6 max-w-2xl">
    <form method="POST" action="{{ route('admin.vendors.save-settings') }}" class="space-y-5">
        @csrf

        <div class="flex items-center gap-3">
            <input type="checkbox" name="vendor_registration_enabled" value="1" id="vr"
                   {{ $settings['vendor_registration_enabled'] == '1' ? 'checked' : '' }} class="w-4 h-4 rounded">
            <label for="vr" class="text-sm font-medium text-gray-700 cursor-pointer">ভেন্ডর রেজিস্ট্রেশন চালু আছে</label>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" name="vendor_auto_approve" value="1" id="vaa"
                   {{ $settings['vendor_auto_approve'] == '1' ? 'checked' : '' }} class="w-4 h-4 rounded">
            <label for="vaa" class="text-sm font-medium text-gray-700 cursor-pointer">নতুন ভেন্ডর অটো অনুমোদন</label>
        </div>

        <div class="flex items-center gap-3">
            <input type="checkbox" name="vendor_product_auto_approve" value="1" id="vpaa"
                   {{ $settings['vendor_product_auto_approve'] == '1' ? 'checked' : '' }} class="w-4 h-4 rounded">
            <label for="vpaa" class="text-sm font-medium text-gray-700 cursor-pointer">ভেন্ডর পণ্য অটো অনুমোদন (গ্লোবাল)</label>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ডিফল্ট কমিশন ধরন</label>
                <select name="default_commission_type"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    <option value="percentage" {{ $settings['default_commission_type'] === 'percentage' ? 'selected' : '' }}>শতাংশ (%)</option>
                    <option value="fixed" {{ $settings['default_commission_type'] === 'fixed' ? 'selected' : '' }}>নির্দিষ্ট (৳)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ডিফল্ট কমিশন মান</label>
                <input type="number" name="default_commission_value" value="{{ $settings['default_commission_value'] }}"
                       step="0.01" min="0" required
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>

        <button type="submit"
                class="bg-[#1a6b3a] hover:bg-[#14532d] text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
            সেটিং সংরক্ষণ করুন
        </button>
    </form>
</div>

@endsection
