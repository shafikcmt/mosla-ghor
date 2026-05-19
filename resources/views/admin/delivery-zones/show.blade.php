@extends('admin.layout')

@section('title', $deliveryZone->zone_name . ' — এলাকা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.delivery-zones.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← জোন তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $deliveryZone->zone_name }}</h1>
</div>

@php
    $typeLabels = ['inside_dhaka' => 'ঢাকার ভেতরে', 'outside_dhaka' => 'ঢাকার বাইরে', 'custom' => 'কাস্টম'];
@endphp

{{-- Zone summary --}}
<div class="bg-white rounded shadow px-6 py-4 mb-6 flex flex-wrap gap-6 text-sm">
    <div><span class="text-gray-500">ধরন:</span> <span class="font-medium">{{ $typeLabels[$deliveryZone->zone_type] ?? $deliveryZone->zone_type }}</span></div>
    <div><span class="text-gray-500">চার্জ:</span> <span class="font-bold">৳{{ number_format($deliveryZone->delivery_charge, 0) }}</span></div>
    @if($deliveryZone->free_delivery_minimum_amount)
        <div><span class="text-gray-500">ফ্রি ডেলিভারি:</span> <span class="font-medium text-green-600">৳{{ number_format($deliveryZone->free_delivery_minimum_amount, 0) }} বা বেশি হলে</span></div>
    @endif
    <div><span class="text-gray-500">স্ট্যাটাস:</span>
        <span class="{{ $deliveryZone->is_active ? 'text-green-600' : 'text-red-500' }} font-medium">
            {{ $deliveryZone->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
        </span>
    </div>
    <a href="{{ route('admin.delivery-zones.edit', $deliveryZone) }}" class="ml-auto text-gray-600 hover:underline text-xs">জোন সম্পাদনা করুন →</a>
</div>

{{-- Locations --}}
<div class="flex items-center justify-between mb-3">
    <h2 class="text-base font-bold text-gray-700">এলাকা / লোকেশন</h2>
</div>

<div class="bg-white rounded shadow overflow-hidden mb-6">
    @if($deliveryZone->locations->isEmpty())
        <div class="px-6 py-8 text-center text-gray-400 text-sm">কোনো এলাকা নেই। নিচে যোগ করুন।</div>
    @else
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">এলাকার নাম</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">কীওয়ার্ড</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">চার্জ</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($deliveryZone->locations as $location)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 font-medium text-gray-800">{{ $location->location_name }}</td>
                <td class="px-5 py-3 text-gray-400 text-xs max-w-xs truncate">{{ $location->keywords ?: '—' }}</td>
                <td class="px-5 py-3 text-right text-gray-700">
                    @if($location->delivery_charge !== null)
                        <span class="font-semibold">৳{{ number_format($location->delivery_charge, 0) }}</span>
                    @else
                        <span class="text-gray-400 text-xs">জোন থেকে (৳{{ number_format($deliveryZone->delivery_charge, 0) }})</span>
                    @endif
                </td>
                <td class="px-5 py-3 text-center">
                    <form action="{{ route('admin.delivery-zones.locations.toggle', [$deliveryZone, $location]) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium {{ $location->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }} transition-colors">
                            {{ $location->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </button>
                    </form>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ route('admin.delivery-zones.locations.edit', [$deliveryZone, $location]) }}"
                           class="text-xs text-gray-600 hover:underline">সম্পাদনা</a>
                        <form action="{{ route('admin.delivery-zones.locations.destroy', [$deliveryZone, $location]) }}" method="POST"
                              onsubmit="return confirm('এলাকা মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>

{{-- Add location form --}}
<div class="bg-white rounded shadow">
    <div class="px-6 py-4 border-b border-gray-100">
        <h3 class="text-sm font-semibold text-gray-700">নতুন এলাকা যোগ করুন</h3>
    </div>
    <form action="{{ route('admin.delivery-zones.locations.store', $deliveryZone) }}" method="POST">
        @csrf
        <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">এলাকার নাম <span class="text-red-500">*</span></label>
                <input type="text" name="location_name" value="{{ old('location_name') }}"
                       placeholder="যেমন: মিরপুর"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('location_name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    কীওয়ার্ড
                    <span class="text-gray-400 font-normal">(অনুসন্ধানের জন্য, কমা দিয়ে)</span>
                </label>
                <input type="text" name="keywords" value="{{ old('keywords') }}"
                       placeholder="mirpur, mirpur-10, mirpur 11"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('keywords') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1.5">
                    আলাদা চার্জ (৳)
                    <span class="text-gray-400 font-normal">(ঐচ্ছিক, খালি = জোন চার্জ)</span>
                </label>
                <input type="number" name="delivery_charge" min="0" step="1" value="{{ old('delivery_charge') }}"
                       placeholder="খালি = ৳{{ number_format($deliveryZone->delivery_charge, 0) }}"
                       class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
                @error('delivery_charge') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
            </div>
        </div>
        <div class="px-6 pb-5">
            <button type="submit" class="bg-gray-800 text-white px-5 py-2 rounded text-sm font-medium hover:bg-gray-700 transition-colors">
                এলাকা যোগ করুন
            </button>
        </div>
    </form>
</div>

@endsection
