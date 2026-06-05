@extends('admin.layout')
@section('title', 'মাল্টিভেন্ডর সেটিং')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.vendors.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ভেন্ডর তালিকা</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">মাল্টিভেন্ডর সেটিং</h1>
</div>

<form method="POST" action="{{ route('admin.vendors.save-settings') }}" class="space-y-6 max-w-2xl">
    @csrf

    {{-- Registration & approval --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h2 class="text-sm font-bold text-gray-800 mb-3">রেজিস্ট্রেশন ও অনুমোদন</h2>
        <div class="divide-y divide-gray-100">
            <x-ui.toggle-row name="vendor_registration_enabled" label="ভেন্ডর রেজিস্ট্রেশন চালু"
                             :checked="$settings['vendor_registration_enabled'] == '1'" />
            <x-ui.toggle-row name="vendor_auto_approve" label="নতুন ভেন্ডর অটো অনুমোদন"
                             :checked="$settings['vendor_auto_approve'] == '1'" />
            <x-ui.toggle-row name="vendor_product_auto_approve" label="ভেন্ডর পণ্য অটো অনুমোদন (গ্লোবাল)"
                             help="বন্ধ থাকলে নতুন পণ্য অ্যাডমিন অনুমোদনের আগে দেখাবে না।"
                             :checked="$settings['vendor_product_auto_approve'] == '1'" />
        </div>
    </div>

    {{-- Feature permissions --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h2 class="text-sm font-bold text-gray-800 mb-3">ভেন্ডর ফিচার অনুমতি</h2>
        <div class="divide-y divide-gray-100">
            <x-ui.toggle-row name="vendor_can_add_product" label="ভেন্ডর পণ্য যোগ করতে পারবে"
                             :checked="$settings['vendor_can_add_product'] == '1'" />
            <x-ui.toggle-row name="vendor_can_manage_stock" label="ভেন্ডর স্টক ম্যানেজ করতে পারবে"
                             :checked="$settings['vendor_can_manage_stock'] == '1'" />
            <x-ui.toggle-row name="vendor_can_create_customer" label="ভেন্ডর লোকাল কাস্টমার যোগ করতে পারবে"
                             :checked="$settings['vendor_can_create_customer'] == '1'" />
            <x-ui.toggle-row name="vendor_can_create_order" label="ভেন্ডর অর্ডার (POS) তৈরি করতে পারবে"
                             :checked="$settings['vendor_can_create_order'] == '1'" />
            <x-ui.toggle-row name="vendor_can_share_whatsapp" label="ভেন্ডর WhatsApp invoice শেয়ার করতে পারবে"
                             :checked="$settings['vendor_can_share_whatsapp'] == '1'" />
            <x-ui.toggle-row name="vendor_can_give_discount" label="ভেন্ডর ডিসকাউন্ট দিতে পারবে"
                             :checked="$settings['vendor_can_give_discount'] == '1'" />
            <x-ui.toggle-row name="vendor_can_allow_due" label="ভেন্ডর বাকি / আংশিক পেমেন্ট অর্ডার নিতে পারবে"
                             :checked="$settings['vendor_can_allow_due'] == '1'" />
            <x-ui.toggle-row name="stock_negative_allowed" label="স্টক না থাকলেও অর্ডার (ব্যাকঅর্ডার) অনুমোদিত"
                             help="বন্ধ থাকলে পর্যাপ্ত স্টক না থাকলে অর্ডার আটকে যাবে।"
                             :checked="$settings['stock_negative_allowed'] == '1'" />
        </div>
    </div>

    {{-- Limits & commission --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h2 class="text-sm font-bold text-gray-800 mb-3">সীমা ও কমিশন</h2>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">সর্বোচ্চ ডিসকাউন্ট (%)</label>
                <input type="number" name="vendor_max_discount_percent" value="{{ old('vendor_max_discount_percent', $settings['vendor_max_discount_percent']) }}"
                       step="0.01" min="0" max="100" required
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Invoice link মেয়াদ (দিন, 0 = কখনো না)</label>
                <input type="number" name="invoice_token_expiry_days" value="{{ old('invoice_token_expiry_days', $settings['invoice_token_expiry_days']) }}"
                       step="1" min="0" required
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
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
                <input type="number" name="default_commission_value" value="{{ old('default_commission_value', $settings['default_commission_value']) }}"
                       step="0.01" min="0" required
                       class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
        </div>
    </div>

    {{-- WhatsApp invoice template --}}
    <div class="bg-white rounded-xl border border-gray-100 p-6">
        <h2 class="text-sm font-bold text-gray-800 mb-1">WhatsApp invoice টেমপ্লেট</h2>
        <p class="text-xs text-gray-400 mb-3">
            টোকেন: <code>{customer_name}</code> <code>{order_number}</code> <code>{total}</code>
            <code>{invoice_link}</code> <code>{reorder_link}</code> <code>{shop_name}</code>
        </p>
        <textarea name="whatsapp_invoice_template" rows="10"
                  class="w-full border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-green-500">{{ old('whatsapp_invoice_template', $settings['whatsapp_invoice_template']) }}</textarea>
    </div>

    <button type="submit"
            class="bg-[#1a6b3a] hover:bg-[#14532d] text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
        সেটিং সংরক্ষণ করুন
    </button>
</form>

@endsection
