@extends('admin.layout')

@section('title', 'ডেলিভারি জোন')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">ডেলিভারি জোন</h1>
    <a href="{{ route('admin.delivery-zones.create') }}"
       class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
        + নতুন জোন
    </a>
</div>

@if($zones->isEmpty())
    <div class="bg-white rounded shadow px-6 py-10 text-center text-gray-400">
        কোনো ডেলিভারি জোন নেই।
        <a href="{{ route('admin.delivery-zones.create') }}" class="text-gray-700 underline ml-1">যোগ করুন</a>
    </div>
@else
<div class="bg-white rounded shadow overflow-hidden">
    <table class="w-full text-sm">
        <thead class="border-b border-gray-200">
            <tr>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">জোন</th>
                <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">ধরন</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">চার্জ</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">এলাকা</th>
                <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">স্ট্যাটাস</th>
                <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($zones as $zone)
            @php
                $typeLabels = ['inside_dhaka' => 'ঢাকার ভেতরে', 'outside_dhaka' => 'ঢাকার বাইরে', 'custom' => 'কাস্টম'];
                $typeColors = ['inside_dhaka' => 'bg-blue-100 text-blue-700', 'outside_dhaka' => 'bg-purple-100 text-purple-700', 'custom' => 'bg-gray-100 text-gray-600'];
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                    <div class="font-semibold text-gray-800">{{ $zone->zone_name }}</div>
                    @if($zone->free_delivery_minimum_amount)
                        <div class="text-[10px] text-green-600 mt-0.5">ফ্রি ডেলিভারি ≥ ৳{{ number_format($zone->free_delivery_minimum_amount, 0) }}</div>
                    @endif
                    @if($zone->sort_order !== null)
                        <div class="text-[10px] text-gray-400">ক্রম: {{ $zone->sort_order }}</div>
                    @endif
                </td>
                <td class="px-5 py-3">
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$zone->zone_type] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $typeLabels[$zone->zone_type] ?? $zone->zone_type }}
                    </span>
                </td>
                <td class="px-5 py-3 text-right font-semibold text-gray-800">৳{{ number_format($zone->delivery_charge, 0) }}</td>
                <td class="px-5 py-3 text-center text-gray-500">{{ $zone->locations_count }}</td>
                <td class="px-5 py-3 text-center">
                    <form action="{{ route('admin.delivery-zones.toggle', $zone) }}" method="POST">
                        @csrf
                        <button type="submit"
                                class="px-2.5 py-1 rounded text-xs font-medium {{ $zone->is_active ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-red-100 text-red-600 hover:bg-red-200' }} transition-colors">
                            {{ $zone->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                        </button>
                    </form>
                </td>
                <td class="px-5 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.delivery-zones.show', $zone) }}"
                           class="text-xs text-blue-600 hover:underline">এলাকা</a>
                        <a href="{{ route('admin.delivery-zones.edit', $zone) }}"
                           class="text-xs text-gray-600 hover:underline">সম্পাদনা</a>
                        <form action="{{ route('admin.delivery-zones.destroy', $zone) }}" method="POST"
                              onsubmit="return confirm('জোন মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

@endsection
