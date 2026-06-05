@extends('admin.layout')
@section('title', 'পার্সেল / কুরিয়ার অর্ডার')

@section('content')
<h2 class="text-lg font-bold text-gray-800 mb-4">পার্সেল / কুরিয়ার অর্ডার</h2>

{{-- Summary stat cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-5">
    <a href="{{ route('admin.courier-orders.index') }}">
        <x-ui.stat-card label="পেন্ডিং পার্সেল" :value="$summary['pending']" color="amber" class="hover:shadow transition-shadow" />
    </a>
    <a href="{{ route('admin.courier-orders.index', ['courier_status' => 'sent_to_courier']) }}">
        <x-ui.stat-card label="কুরিয়ারে পাঠানো" :value="$summary['sent']" color="blue" class="hover:shadow transition-shadow" />
    </a>
    <a href="{{ route('admin.courier-orders.index', ['courier_status' => 'delivered']) }}">
        <x-ui.stat-card label="ডেলিভারড" :value="$summary['delivered']" color="green" class="hover:shadow transition-shadow" />
    </a>
    <a href="{{ route('admin.courier-orders.index', ['courier_status' => 'returned']) }}">
        <x-ui.stat-card label="ফেরত" :value="$summary['returned']" color="red" class="hover:shadow transition-shadow" />
    </a>
</div>

{{-- Filters --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
    <form method="GET" action="{{ route('admin.courier-orders.index') }}" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">কুরিয়ার</label>
            <select name="courier_id" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                <option value="">— সকল কুরিয়ার —</option>
                @foreach($couriers as $c)
                <option value="{{ $c->id }}" {{ request('courier_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">কুরিয়ার স্ট্যাটাস</label>
            <select name="courier_status" class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
                <option value="">— সকল স্ট্যাটাস —</option>
                @foreach($courierStatuses as $key => $label)
                <option value="{{ $key }}" {{ request('courier_status') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">তারিখ থেকে</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">তারিখ পর্যন্ত</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                   class="border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none">
        </div>
        <button type="submit"
                class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
            ফিল্টার করুন
        </button>
        <a href="{{ route('admin.courier-orders.index') }}"
           class="text-sm text-gray-500 hover:text-gray-700 border border-gray-300 rounded px-4 py-2">রিসেট</a>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">অর্ডার</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">গ্রাহক</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">কুরিয়ার</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">জোন</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">ডেলিভারি চার্জ</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">কুরিয়ার খরচ</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">ট্র্যাকিং</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">পাঠানোর তারিখ</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders as $order)
            @php
                $csColors = [
                    'pending'           => 'bg-yellow-100 text-yellow-700',
                    'processing'        => 'bg-indigo-100 text-indigo-700',
                    'ready_for_courier' => 'bg-blue-100 text-blue-700',
                    'sent_to_courier'   => 'bg-cyan-100 text-cyan-700',
                    'picked_up'         => 'bg-teal-100 text-teal-700',
                    'in_transit'        => 'bg-purple-100 text-purple-700',
                    'delivered'         => 'bg-green-100 text-green-700',
                    'returned'          => 'bg-orange-100 text-orange-700',
                    'cancelled'         => 'bg-red-100 text-red-700',
                    'failed_delivery'   => 'bg-red-100 text-red-700',
                ];
            @endphp
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800">{{ $order->customer_name }}</div>
                    <div class="text-gray-400 text-xs">{{ $order->mobile_number }}</div>
                </td>
                <td class="px-4 py-3 text-gray-700">{{ $order->selectedCourier?->name ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs">{{ $order->delivery_zone_name ?? '—' }}</td>
                <td class="px-4 py-3 text-right text-gray-700">৳ {{ number_format($order->delivery_charge, 0) }}</td>
                <td class="px-4 py-3 text-right text-gray-600">
                    {{ $order->courier_cost !== null ? '৳ ' . number_format($order->courier_cost, 0) : '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                    @if($order->courier_status)
                    <span class="px-2 py-1 rounded text-xs font-semibold {{ $csColors[$order->courier_status] ?? 'bg-gray-100 text-gray-500' }}">
                        {{ $courierStatuses[$order->courier_status] ?? $order->courier_status }}
                    </span>
                    @else
                    <span class="text-gray-300 text-xs">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $order->tracking_id ?? '—' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs">
                    {{ $order->sent_to_courier_at?->format('d M Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="text-xs text-blue-600 hover:underline">দেখুন</a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-400">কোনো পার্সেল অর্ডার নেই।</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
<div class="mt-4">{{ $orders->links() }}</div>
@endif
@endsection
