@extends('vendor.layout')
@section('title', 'অর্ডারসমূহ')

@php
$fsColors = [
    'pending'             => 'bg-yellow-100 text-yellow-700',
    'processing'          => 'bg-indigo-100 text-indigo-700',
    'packed'              => 'bg-blue-100 text-blue-700',
    'ready_for_pickup'    => 'bg-cyan-100 text-cyan-700',
    'handed_to_courier'   => 'bg-teal-100 text-teal-700',
    'cancelled_by_vendor' => 'bg-red-100 text-red-700',
];
@endphp

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-800">আমার অর্ডার</h2>
        <p class="text-sm text-gray-500 mt-0.5">মোট {{ $vendorOrders->total() }}টি অর্ডার</p>
    </div>
    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()"
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            <option value="">— সব অবস্থা —</option>
            @foreach($fulfillmentStatuses as $key => $label)
            <option value="{{ $key }}" {{ request('status') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($vendorOrders->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">এখনো কোনো অর্ডার নেই।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অর্ডার নং</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">তারিখ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">সাবটোটাল</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">ফুলফিলমেন্ট</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">কুরিয়ার</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">বিস্তারিত</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($vendorOrders as $vo)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800">{{ $vo->order?->order_number }}</td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs">{{ $vo->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 font-mono font-semibold">৳{{ number_format($vo->subtotal, 0) }}</td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium {{ $fsColors[$vo->fulfillment_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $fulfillmentStatuses[$vo->fulfillment_status] ?? $vo->fulfillment_status }}
                    </span>
                </td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs">
                    {{ $vo->courier_name ?: '—' }}
                    @if($vo->tracking_number)
                    <span class="font-mono text-gray-400 ml-1">{{ $vo->tracking_number }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right">
                    <a href="{{ route('vendor.orders.show', $vo) }}"
                       class="text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded px-2.5 py-1">দেখুন</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($vendorOrders->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">
        {{ $vendorOrders->links() }}
    </div>
    @endif
    @endif
</div>
@endsection
