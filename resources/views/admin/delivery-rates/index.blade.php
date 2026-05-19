@extends('admin.layout')
@section('title', 'ডেলিভারি রেট')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-lg font-bold text-gray-800">ডেলিভারি রেট</h2>
    <a href="{{ route('admin.delivery-rates.create') }}"
       class="bg-[#14532d] text-white text-sm px-4 py-2 rounded hover:bg-[#0d3520] transition-colors">
        + নতুন রেট
    </a>
</div>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">কুরিয়ার</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">জোন</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">ওজন (গ্রাম)</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">কুরিয়ার খরচ (৳)</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">গ্রাহক চার্জ (৳)</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">COD %</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($rates as $rate)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $rate->courier->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $rate->zone->zone_name ?? ($rate->zone_type ? ucfirst(str_replace('_',' ',$rate->zone_type)) : 'সকল জোন') }}
                </td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $rate->min_weight }}–{{ $rate->max_weight }}g</td>
                <td class="px-4 py-3 text-right text-gray-700">{{ number_format($rate->courier_cost, 2) }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($rate->customer_delivery_charge, 2) }}</td>
                <td class="px-4 py-3 text-center text-gray-600">{{ $rate->cod_percentage }}%</td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $rate->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                        {{ $rate->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.delivery-rates.edit', $rate) }}"
                           class="text-xs text-blue-600 hover:underline">সম্পাদনা</a>
                        <form method="POST" action="{{ route('admin.delivery-rates.destroy', $rate) }}"
                              onsubmit="return confirm('এই রেট মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button class="text-xs text-red-500 hover:underline">মুছুন</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">কোনো রেট নেই। নতুন রেট যোগ করুন।</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
