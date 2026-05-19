@extends('admin.layout')

@section('title', 'এলাকা সম্পাদনা: ' . $location->location_name)

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.delivery-zones.show', $deliveryZone) }}" class="text-gray-400 hover:text-gray-600 text-sm">← {{ $deliveryZone->zone_name }}</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">সম্পাদনা: {{ $location->location_name }}</h1>
</div>

<div class="bg-white rounded shadow">
    <form action="{{ route('admin.delivery-zones.locations.update', [$deliveryZone, $location]) }}" method="POST">
        @csrf @method('PUT')

        <div class="px-6 py-5 space-y-5">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">এলাকার নাম <span class="text-red-500">*</span></label>
                    <input type="text" name="location_name"
                           value="{{ old('location_name', $location->location_name) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('location_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                        আলাদা চার্জ (৳)
                        <span class="text-gray-400 font-normal">(খালি = জোন চার্জ ৳{{ number_format($deliveryZone->delivery_charge, 0) }})</span>
                    </label>
                    <input type="number" name="delivery_charge" min="0" step="1"
                           value="{{ old('delivery_charge', $location->delivery_charge) }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                    @error('delivery_charge') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    কীওয়ার্ড
                    <span class="text-gray-400 font-normal">(অনুসন্ধানের জন্য, কমা দিয়ে আলাদা করুন)</span>
                </label>
                <input type="text" name="keywords"
                       value="{{ old('keywords', $location->keywords) }}"
                       placeholder="mirpur, mirpur-10, mirpur 11"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('keywords') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $location->is_active ? '1' : '') ? 'checked' : '' }}
                       class="w-4 h-4 accent-gray-800">
                <div>
                    <div class="text-sm font-semibold text-gray-700">এলাকা সক্রিয়</div>
                    <div class="text-xs text-gray-400">নিষ্ক্রিয় এলাকা অর্ডার ফর্মে দেখাবে না</div>
                </div>
            </label>

        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex gap-3">
            <button type="submit" class="bg-gray-800 text-white px-6 py-2 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                আপডেট করুন
            </button>
            <a href="{{ route('admin.delivery-zones.show', $deliveryZone) }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded text-sm hover:bg-gray-200 transition-colors">
                বাতিল
            </a>
        </div>
    </form>
</div>

@endsection
