@extends('admin.layout')
@section('title', 'নতুন ডেলিভারি রেট')

@section('content')
<div class="mb-5">
    <a href="{{ route('admin.delivery-rates.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← ডেলিভারি রেট তালিকায় ফিরুন</a>
</div>

<div class="bg-white rounded shadow p-6 max-w-2xl">
    <h2 class="text-base font-bold text-gray-800 mb-5">নতুন ডেলিভারি রেট যোগ করুন</h2>

    <form method="POST" action="{{ route('admin.delivery-rates.store') }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">কুরিয়ার <span class="text-red-500">*</span></label>
                <select name="courier_id" required class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <option value="">— কুরিয়ার বেছে নিন —</option>
                    @foreach($couriers as $courier)
                    <option value="{{ $courier->id }}" {{ old('courier_id') == $courier->id ? 'selected' : '' }}>{{ $courier->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">জোন টাইপ</label>
                <select name="zone_type" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <option value="">— সকল জোন —</option>
                    @foreach($zoneTypes as $key => $label)
                    <option value="{{ $key }}" {{ old('zone_type') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">নির্দিষ্ট ডেলিভারি জোন <span class="text-gray-400 text-xs">(ঐচ্ছিক — জোন টাইপের চেয়ে অগ্রাধিকার পাবে)</span></label>
            <select name="delivery_zone_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                <option value="">— কোনো নির্দিষ্ট জোন নয় —</option>
                @foreach($zones as $zone)
                <option value="{{ $zone->id }}" {{ old('delivery_zone_id') == $zone->id ? 'selected' : '' }}>{{ $zone->zone_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ন্যূনতম ওজন (গ্রাম) <span class="text-red-500">*</span></label>
                <input type="number" name="min_weight" value="{{ old('min_weight', 0) }}" min="0" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">সর্বোচ্চ ওজন (গ্রাম) <span class="text-red-500">*</span></label>
                <input type="number" name="max_weight" value="{{ old('max_weight', 1000) }}" min="1" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">কুরিয়ার খরচ (৳) <span class="text-red-500">*</span></label>
                <input type="number" name="courier_cost" value="{{ old('courier_cost', 0) }}" min="0" step="0.01" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">গ্রাহক ডেলিভারি চার্জ (৳) <span class="text-red-500">*</span></label>
                <input type="number" name="customer_delivery_charge" value="{{ old('customer_delivery_charge', 0) }}" min="0" step="0.01" required
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">COD চার্জ (%)</label>
                <input type="number" name="cod_percentage" value="{{ old('cod_percentage', 0) }}" min="0" max="100" step="0.01"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ফেরত চার্জ (৳)</label>
                <input type="number" name="return_charge" value="{{ old('return_charge', 0) }}" min="0" step="0.01"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-[#14532d] focus:outline-none">
            </div>
        </div>

        <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                   class="w-4 h-4 accent-[#14532d]">
            সক্রিয়
        </label>

        <div class="flex gap-3 pt-2">
            <button type="submit"
                    class="bg-[#14532d] text-white text-sm px-5 py-2 rounded hover:bg-[#0d3520] transition-colors">
                সংরক্ষণ করুন
            </button>
            <a href="{{ route('admin.delivery-rates.index') }}"
               class="text-sm text-gray-500 hover:text-gray-800 px-5 py-2 border border-gray-300 rounded">বাতিল</a>
        </div>
    </form>
</div>
@endsection
