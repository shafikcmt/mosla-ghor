@extends('admin.layout')

@section('title', 'অর্ডার #' . $order->order_number)

@push('styles')
<style>
@media print {
    nav, .no-print { display: none !important; }
    body { background: white !important; }
    main { max-width: 100% !important; padding: 1rem !important; }
    .print-shadow { box-shadow: none !important; }
}
</style>
@endpush

@section('content')

@php
    $oColors = [
        'pending'    => 'bg-yellow-100 text-yellow-700',
        'confirmed'  => 'bg-blue-100 text-blue-700',
        'processing' => 'bg-indigo-100 text-indigo-700',
        'shipped'    => 'bg-cyan-100 text-cyan-700',
        'delivered'  => 'bg-green-100 text-green-700',
        'cancelled'  => 'bg-red-100 text-red-700',
    ];
    $oLabels = [
        'pending'    => 'অপেক্ষায়',
        'confirmed'  => 'নিশ্চিত',
        'processing' => 'প্রসেসিং',
        'shipped'    => 'শিপড',
        'delivered'  => 'ডেলিভারড',
        'cancelled'  => 'বাতিল',
    ];
    $pColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'verified' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
    $pLabels = ['pending' => 'পেমেন্ট অপেক্ষায়', 'verified' => 'পেমেন্ট যাচাই হয়েছে', 'failed' => 'পেমেন্ট ব্যর্থ'];
    $typeLabels = ['single_product' => 'একক পণ্য', 'custom' => 'কাস্টম কম্বো', 'fixed_combo' => 'ফিক্সড কম্বো', 'retail' => 'রিটেইল', 'wholesale' => 'হোলসেল'];
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

{{-- Top bar --}}
<div class="no-print flex items-center justify-between mb-5">
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← অর্ডার তালিকায় ফিরুন</a>
    <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank"
       class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
        🖨️ Print Invoice
    </a>
</div>

<div class="print-shadow bg-white rounded shadow divide-y divide-gray-100">

    {{-- Header --}}
    <div class="px-6 py-4 flex items-start justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-800">অর্ডার #{{ $order->order_number }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div class="text-right space-y-1">
            <span class="inline-block px-2.5 py-1 rounded text-xs font-semibold {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $oLabels[$order->order_status] ?? $order->order_status }}
            </span>
            <br>
            <span class="inline-block px-2.5 py-1 rounded text-xs font-semibold {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
            </span>
            @if($order->courier_status)
            <br>
            <span class="inline-block px-2.5 py-1 rounded text-xs font-semibold {{ $csColors[$order->courier_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $courierStatuses[$order->courier_status] ?? $order->courier_status }}
            </span>
            @endif
        </div>
    </div>

    {{-- Customer Information --}}
    <div class="px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">গ্রাহকের তথ্য</h3>
        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <div>
                <span class="text-gray-500">নাম:</span>
                <span class="ml-2 text-gray-800 font-medium">{{ $order->customer_name }}</span>
            </div>
            <div>
                <span class="text-gray-500">মোবাইল:</span>
                <span class="ml-2 text-gray-800">{{ $order->mobile_number }}</span>
            </div>
            @if($order->alternative_number)
            <div>
                <span class="text-gray-500">বিকল্প মোবাইল:</span>
                <span class="ml-2 text-gray-800">{{ $order->alternative_number }}</span>
            </div>
            @endif
            <div>
                <span class="text-gray-500">অর্ডারের ধরন:</span>
                <span class="ml-2 text-gray-800">{{ $typeLabels[$order->order_type] ?? $order->order_type }}</span>
            </div>
            @if($order->division_name || $order->district_name)
            <div class="col-span-2">
                <span class="text-gray-500">বিভাগ / জেলা / উপজেলা:</span>
                <span class="ml-2 text-gray-800">
                    {{ implode(' › ', array_filter([$order->division_name, $order->district_name, $order->upazila_name, $order->union_name])) }}
                </span>
            </div>
            @endif
            <div class="col-span-2">
                <span class="text-gray-500">বাড়ি/রাস্তা:</span>
                <span class="ml-2 text-gray-800">{{ $order->full_address }}</span>
            </div>
            @if($order->delivery_zone_name || $order->delivery_area)
            <div>
                <span class="text-gray-500">ডেলিভারি জোন:</span>
                <span class="ml-2 text-gray-800 font-medium">
                    {{ $order->delivery_zone_name ?: ($order->delivery_area === 'inside_dhaka' ? 'ঢাকার ভেতরে' : 'ঢাকার বাইরে') }}
                    @if($order->zone_overridden) <span class="text-orange-500 text-xs">(পরিবর্তিত)</span> @endif
                </span>
            </div>
            @endif
            @if($order->delivery_location_name)
            <div>
                <span class="text-gray-500">ডেলিভারি এলাকা:</span>
                <span class="ml-2 text-gray-800">{{ $order->delivery_location_name }}</span>
            </div>
            @endif
            <div>
                <span class="text-gray-500">ডেলিভারি চার্জ:</span>
                <span class="ml-2 text-gray-800 font-semibold">৳ {{ number_format($order->delivery_charge, 2) }}</span>
                @if($order->delivery_charge_overridden) <span class="text-orange-500 text-xs ml-1">(ওভাররাইড)</span> @endif
            </div>
            @if($order->weight_gram)
            <div>
                <span class="text-gray-500">মোট ওজন:</span>
                <span class="ml-2 text-gray-800">{{ $order->weight_gram >= 1000 ? ($order->weight_gram/1000).'kg' : $order->weight_gram.'g' }}</span>
            </div>
            @endif
            @if($order->order_note)
            <div class="col-span-2">
                <span class="text-gray-500">নোট:</span>
                <span class="ml-2 text-gray-700 italic">{{ $order->order_note }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Order Items --}}
    <div class="px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">অর্ডারের পণ্য</h3>
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="pb-2 text-left font-semibold text-gray-600">পণ্যের নাম</th>
                    <th class="pb-2 text-right font-semibold text-gray-600">পরিমাণ</th>
                    <th class="pb-2 text-right font-semibold text-gray-600">একক মূল্য (৳)</th>
                    <th class="pb-2 text-right font-semibold text-gray-600">মোট (৳)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($order->items as $item)
                <tr>
                    <td class="py-2.5 text-gray-800">{{ $item->product_name }}</td>
                    <td class="py-2.5 text-right text-gray-600">
                        @if($item->quantity_gram >= 1000)
                            {{ $item->quantity_gram / 1000 }} কেজি
                        @else
                            {{ $item->quantity_gram }} গ্রাম
                        @endif
                    </td>
                    <td class="py-2.5 text-right text-gray-700">{{ number_format($item->unit_price, 2) }}</td>
                    <td class="py-2.5 text-right font-semibold text-gray-800">{{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Cost Breakdown --}}
    <div class="px-6 py-4">
        <div class="ml-auto max-w-xs text-sm space-y-1.5">
            <div class="flex justify-between text-gray-600">
                <span>সাবটোটাল</span><span>৳ {{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>প্যাকেজিং</span><span>৳ {{ number_format($order->packaging_cost, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>ডেলিভারি চার্জ</span><span>৳ {{ number_format($order->delivery_charge, 2) }}</span>
            </div>
            @if($order->cod_charge)
            <div class="flex justify-between text-gray-600">
                <span>COD চার্জ</span><span>৳ {{ number_format($order->cod_charge, 2) }}</span>
            </div>
            @endif
            <div class="flex justify-between font-bold text-gray-800 border-t border-gray-200 pt-2 text-base">
                <span>সর্বমোট</span><span>৳ {{ number_format($order->grand_total, 2) }}</span>
            </div>
            @if($order->courier_cost !== null)
            <div class="flex justify-between text-gray-500 text-xs border-t border-dashed border-gray-200 pt-2 mt-1">
                <span>কুরিয়ার খরচ (admin)</span><span>৳ {{ number_format($order->courier_cost, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">পেমেন্ট তথ্য</h3>
        @php $methodLabels = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট']; @endphp
        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <div>
                <span class="text-gray-500">পেমেন্ট পদ্ধতি:</span>
                <span class="ml-2 text-gray-800 font-medium">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-gray-500">পেমেন্ট স্ট্যাটাস:</span>
                <span class="ml-2 font-medium {{ $pColors[$order->payment_status] ?? '' }} px-2 py-0.5 rounded text-xs">
                    {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                </span>
            </div>
            @if($order->sender_number)
            <div><span class="text-gray-500">সেন্ডার নম্বর:</span><span class="ml-2 text-gray-800 font-medium">{{ $order->sender_number }}</span></div>
            @endif
            @if($order->transaction_id)
            <div><span class="text-gray-500">ট্রানজেকশন আইডি:</span><span class="ml-2 text-gray-800 font-mono font-medium">{{ $order->transaction_id }}</span></div>
            @endif
            @if($order->paid_amount !== null)
            <div><span class="text-gray-500">পেমেন্ট করা পরিমাণ:</span><span class="ml-2 text-gray-800 font-semibold">৳ {{ number_format($order->paid_amount, 2) }}</span></div>
            @endif
        </div>

        {{-- Payment screenshot --}}
        @if($order->payment_screenshot)
        <div class="mt-4">
            <p class="text-xs text-gray-500 mb-2">পেমেন্ট স্ক্রিনশট:</p>
            <a href="{{ asset('storage/' . $order->payment_screenshot) }}" target="_blank" rel="noopener"
               class="inline-block">
                <img src="{{ asset('storage/' . $order->payment_screenshot) }}"
                     alt="পেমেন্ট স্ক্রিনশট"
                     class="h-32 w-auto rounded border border-gray-200 shadow-sm hover:opacity-90 transition-opacity object-cover cursor-zoom-in">
            </a>
            <p class="text-[10px] text-gray-400 mt-1">ক্লিক করলে পূর্ণ ছবি নতুন ট্যাবে খুলবে</p>
        </div>
        @endif

        {{-- Quick payment status actions (no-print) --}}
        @if($order->payment_method !== 'cash_on_delivery')
        <div class="no-print mt-4 pt-4 border-t border-gray-100">
            <p class="text-xs text-gray-500 mb-2 font-medium">পেমেন্ট দ্রুত যাচাই:</p>
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}" class="flex flex-wrap gap-2">
                @csrf
                <input type="hidden" name="order_status" value="{{ $order->order_status }}">
                <button type="submit" name="payment_status" value="verified"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors
                               {{ $order->payment_status === 'verified' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-700 hover:bg-green-600 hover:text-white' }}">
                    ✓ যাচাই হয়েছে
                </button>
                <button type="submit" name="payment_status" value="failed"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors
                               {{ $order->payment_status === 'failed' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-700 hover:bg-red-600 hover:text-white' }}">
                    ✗ ব্যর্থ
                </button>
                <button type="submit" name="payment_status" value="pending"
                        class="px-3 py-1.5 rounded text-xs font-semibold transition-colors
                               {{ $order->payment_status === 'pending' ? 'bg-yellow-500 text-white' : 'bg-yellow-100 text-yellow-700 hover:bg-yellow-500 hover:text-white' }}">
                    ↺ পেন্ডিং
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- ── Courier / Delivery Section ─────────────────────────────── --}}
    <div class="no-print px-6 py-5 bg-blue-50">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">কুরিয়ার / ডেলিভারি ম্যানেজমেন্ট</h3>

        {{-- Current courier info --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-5 text-sm">
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">নির্বাচিত কুরিয়ার</div>
                <div class="font-semibold text-gray-800">{{ $order->selectedCourier?->name ?? '—' }}</div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">সাজেস্টেড কুরিয়ার</div>
                <div class="font-semibold text-gray-600">{{ $order->suggestedCourier?->name ?? '—' }}</div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">কুরিয়ার স্ট্যাটাস</div>
                <div class="font-semibold text-gray-800">
                    @if($order->courier_status)
                        <span class="px-2 py-0.5 rounded text-xs {{ $csColors[$order->courier_status] ?? 'bg-gray-100' }}">
                            {{ $courierStatuses[$order->courier_status] ?? $order->courier_status }}
                        </span>
                    @else
                        <span class="text-gray-400">—</span>
                    @endif
                </div>
            </div>
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">ট্র্যাকিং আইডি</div>
                <div class="font-mono text-xs text-gray-800">{{ $order->tracking_id ?: '—' }}</div>
            </div>
            @if($order->consignment_id)
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">Consignment আইডি</div>
                <div class="font-mono text-xs text-gray-800">{{ $order->consignment_id }}</div>
            </div>
            @endif
            @if($order->sent_to_courier_at)
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">পাঠানোর তারিখ</div>
                <div class="text-xs text-gray-800">{{ $order->sent_to_courier_at->format('d M Y, h:i A') }}</div>
            </div>
            @endif
            @if($order->delivered_at)
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">ডেলিভারির তারিখ</div>
                <div class="text-xs text-gray-800">{{ $order->delivered_at->format('d M Y') }}</div>
            </div>
            @endif
        </div>

        {{-- Update courier form --}}
        <form method="POST" action="{{ route('admin.orders.updateCourier', $order) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">কুরিয়ার পরিবর্তন</label>
                    <select name="selected_courier_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">— কুরিয়ার বেছে নিন —</option>
                        @foreach($couriers as $courier)
                        <option value="{{ $courier->id }}" {{ $order->selected_courier_id == $courier->id ? 'selected' : '' }}>
                            {{ $courier->name }} {{ $courier->status !== 'active' ? '(নিষ্ক্রিয়)' : '' }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">জোন পরিবর্তন</label>
                    <select name="delivery_zone_id" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">— বর্তমান জোন রাখুন —</option>
                        @foreach($zones as $zone)
                        <option value="{{ $zone->id }}" {{ $order->delivery_zone_id == $zone->id ? 'selected' : '' }}>{{ $zone->zone_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">কুরিয়ার স্ট্যাটাস</label>
                    <select name="courier_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                        <option value="">— পরিবর্তন না করুন —</option>
                        @foreach($courierStatuses as $key => $label)
                        <option value="{{ $key }}" {{ $order->courier_status === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ডেলিভারি চার্জ ওভাররাইড (৳)</label>
                    <input type="number" name="delivery_charge" placeholder="{{ number_format($order->delivery_charge, 2) }}"
                           min="0" step="0.01"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">কুরিয়ার খরচ (৳)</label>
                    <input type="number" name="courier_cost" value="{{ $order->courier_cost }}"
                           min="0" step="0.01"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">ট্র্যাকিং আইডি</label>
                    <input type="text" name="tracking_id" value="{{ $order->tracking_id }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Consignment আইডি</label>
                    <input type="text" name="consignment_id" value="{{ $order->consignment_id }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">কুরিয়ার নোট</label>
                    <input type="text" name="courier_note" value="{{ $order->courier_note }}"
                           class="w-full border border-gray-300 rounded px-3 py-2 text-sm bg-white focus:ring-2 focus:ring-[#14532d] focus:outline-none">
                </div>
            </div>
            <div>
                <button type="submit"
                        class="bg-[#14532d] text-white text-sm px-5 py-2 rounded hover:bg-[#0d3520] transition-colors">
                    কুরিয়ার তথ্য আপডেট করুন
                </button>
            </div>
        </form>

        {{-- Send to Courier buttons --}}
        <div class="mt-5 pt-4 border-t border-blue-200">
            <h4 class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-3">কুরিয়ারে পাঠান</h4>
            <div class="flex flex-wrap gap-3">

                {{-- Steadfast --}}
                <form method="POST" action="{{ route('admin.orders.sendToCourier', $order) }}">
                    @csrf
                    @php $steadfast = $couriers->firstWhere('slug', 'steadfast'); @endphp
                    <button type="submit"
                            onclick="return confirm('Steadfast-এ পাঠাবেন?')"
                            class="bg-blue-600 text-white text-sm px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                        📦 Steadfast-এ পাঠান
                        @if($steadfast && !$steadfast->api_enabled)
                        <span class="text-blue-200 text-xs">(ম্যানুয়াল)</span>
                        @endif
                    </button>
                </form>

                {{-- Pathao --}}
                <form method="POST" action="{{ route('admin.orders.sendToCourier', $order) }}">
                    @csrf
                    <button type="button"
                            onclick="alert('Pathao ম্যানুয়াল বুকিং: ট্র্যাকিং আইডি যোগ করুন উপরের ফর্মে।')"
                            class="bg-orange-500 text-white text-sm px-4 py-2 rounded hover:bg-orange-600 transition-colors">
                        🚴 Pathao বুকিং
                        <span class="text-orange-200 text-xs">(ম্যানুয়াল)</span>
                    </button>
                </form>

                {{-- Sundarban --}}
                <form method="POST" action="{{ route('admin.orders.sendToCourier', $order) }}">
                    @csrf
                    <button type="button"
                            onclick="alert('Sundarban ম্যানুয়াল বুকিং: উপরে ট্র্যাকিং আইডি এবং Consignment যোগ করুন।')"
                            class="bg-green-700 text-white text-sm px-4 py-2 rounded hover:bg-green-800 transition-colors">
                        🏢 Sundarban বুকিং
                        <span class="text-green-300 text-xs">(ম্যানুয়াল)</span>
                    </button>
                </form>

                {{-- Mark Delivered --}}
                <form method="POST" action="{{ route('admin.orders.markDelivered', $order) }}"
                      onsubmit="return confirm('ডেলিভারড চিহ্নিত করবেন?')">
                    @csrf
                    <button type="submit"
                            class="bg-green-600 text-white text-sm px-4 py-2 rounded hover:bg-green-700 transition-colors">
                        ✓ ডেলিভারড
                    </button>
                </form>

                {{-- Mark Returned --}}
                <form method="POST" action="{{ route('admin.orders.markReturned', $order) }}"
                      onsubmit="return confirm('ফেরত চিহ্নিত করবেন?')">
                    @csrf
                    <button type="submit"
                            class="bg-orange-600 text-white text-sm px-4 py-2 rounded hover:bg-orange-700 transition-colors">
                        ↩ ফেরত
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Stock Section ─────────────────────────────── --}}
    <div class="no-print px-6 py-5 bg-emerald-50">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-4">স্টক ম্যানেজমেন্ট</h3>
        <div class="flex flex-wrap items-start gap-4 text-sm">
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">স্টক স্ট্যাটাস</div>
                @if($order->stock_restored_at)
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-orange-100 text-orange-700">স্টক ফেরত দেওয়া হয়েছে</span>
                @elseif($order->stock_deducted_at)
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-green-100 text-green-700">স্টক কাটা হয়েছে</span>
                @else
                    <span class="px-2 py-1 rounded text-xs font-semibold bg-gray-100 text-gray-500">স্টক কাটা হয়নি</span>
                @endif
            </div>
            @if($order->stock_deducted_at)
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">কাটার সময়</div>
                <div class="text-xs text-gray-800">{{ $order->stock_deducted_at->format('d M Y, h:i A') }}</div>
            </div>
            @endif
            @if($order->stock_restored_at)
            <div class="bg-white rounded p-3 shadow-sm">
                <div class="text-xs text-gray-400 mb-1">পুনরুদ্ধারের সময়</div>
                <div class="text-xs text-gray-800">{{ $order->stock_restored_at->format('d M Y, h:i A') }}</div>
            </div>
            @endif
        </div>
        @if($order->stock_deducted_at && !$order->stock_restored_at)
        <div class="mt-4">
            <form method="POST" action="{{ route('admin.orders.restoreStock', $order) }}"
                  onsubmit="return confirm('স্টক পুনরুদ্ধার করবেন? পণ্যের স্টক পূর্বের মতো বাড়ানো হবে।')">
                @csrf
                <button type="submit"
                        class="bg-orange-600 text-white text-sm px-4 py-2 rounded hover:bg-orange-700 transition-colors">
                    স্টক পুনরুদ্ধার করুন
                </button>
            </form>
        </div>
        @endif
    </div>

    {{-- Status Update Form --}}
    <div class="no-print px-6 py-5 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">অর্ডার ও পেমেন্ট স্ট্যাটাস আপডেট</h3>
        <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="block text-xs text-gray-500 mb-1" for="payment_status">পেমেন্ট স্ট্যাটাস</label>
                <select name="payment_status" id="payment_status"
                        class="border border-gray-300 rounded text-sm px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="pending"  {{ $order->payment_status === 'pending'  ? 'selected' : '' }}>অপেক্ষায়</option>
                    <option value="verified" {{ $order->payment_status === 'verified' ? 'selected' : '' }}>যাচাই হয়েছে</option>
                    <option value="failed"   {{ $order->payment_status === 'failed'   ? 'selected' : '' }}>ব্যর্থ</option>
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1" for="order_status">অর্ডার স্ট্যাটাস</label>
                <select name="order_status" id="order_status"
                        class="border border-gray-300 rounded text-sm px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <option value="pending"    {{ $order->order_status === 'pending'    ? 'selected' : '' }}>অপেক্ষায়</option>
                    <option value="confirmed"  {{ $order->order_status === 'confirmed'  ? 'selected' : '' }}>নিশ্চিত</option>
                    <option value="processing" {{ $order->order_status === 'processing' ? 'selected' : '' }}>প্রসেসিং</option>
                    <option value="shipped"    {{ $order->order_status === 'shipped'    ? 'selected' : '' }}>শিপড</option>
                    <option value="delivered"  {{ $order->order_status === 'delivered'  ? 'selected' : '' }}>ডেলিভারড</option>
                    <option value="cancelled"  {{ $order->order_status === 'cancelled'  ? 'selected' : '' }}>বাতিল</option>
                </select>
            </div>
            <button type="submit"
                    class="bg-gray-800 text-white text-sm px-5 py-2 rounded hover:bg-gray-700 transition-colors">
                আপডেট করুন
            </button>
        </form>
    </div>

</div>

@endsection
