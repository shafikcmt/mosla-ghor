@extends('vendor.layout')
@section('title', 'অর্ডার বিস্তারিত')

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

@if(session('success'))
<div class="mb-4 px-4 py-3 rounded bg-green-100 text-green-800 text-sm border border-green-200">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div class="mb-4 px-4 py-3 rounded bg-red-100 text-red-800 text-sm border border-red-200">
    @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
</div>
@endif

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.orders.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← অর্ডার তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $vendorOrder->order?->order_number }}</h1>
    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $fsColors[$vendorOrder->fulfillment_status] ?? 'bg-gray-100 text-gray-600' }}">
        {{ $fulfillmentStatuses[$vendorOrder->fulfillment_status] ?? $vendorOrder->fulfillment_status }}
    </span>
</div>

{{-- Info cards row --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">

    {{-- Order summary --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">আমার সারসংক্ষেপ</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">তারিখ</dt>
                <dd class="font-medium">{{ $vendorOrder->created_at->format('d M Y, h:i A') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">সাবটোটাল</dt>
                <dd class="font-mono font-semibold">৳{{ number_format($vendorOrder->subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">কমিশন</dt>
                <dd class="font-mono text-red-600">- ৳{{ number_format($vendorOrder->commission_amount, 2) }}</dd>
            </div>
            <div class="flex justify-between border-t pt-2">
                <dt class="text-gray-700 font-semibold">প্রাপ্য পরিমাণ</dt>
                <dd class="font-mono font-bold text-green-700">৳{{ number_format($vendorOrder->payable_amount, 2) }}</dd>
            </div>
        </dl>
    </div>

    {{-- Customer / delivery info --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">কাস্টমার ও ডেলিভারি</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">নাম</dt>
                <dd class="font-medium">{{ $vendorOrder->order?->customer_name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">মোবাইল</dt>
                <dd class="font-mono">{{ $vendorOrder->order?->mobile_number }}</dd>
            </div>
            @if($vendorOrder->order?->delivery_zone_name)
            <div class="flex justify-between">
                <dt class="text-gray-500">জোন</dt>
                <dd class="text-gray-700">{{ $vendorOrder->order->delivery_zone_name }}</dd>
            </div>
            @endif
            @if($vendorOrder->order?->full_address)
            <div>
                <dt class="text-gray-500 mb-0.5">ঠিকানা</dt>
                <dd class="text-gray-700 text-xs leading-relaxed">
                    {{ implode(', ', array_filter([
                        $vendorOrder->order->full_address,
                        $vendorOrder->order->upazila_name,
                        $vendorOrder->order->district_name,
                        $vendorOrder->order->division_name,
                    ])) }}
                </dd>
            </div>
            @endif
            @if($vendorOrder->order?->order_note)
            <div>
                <dt class="text-gray-500">নোট</dt>
                <dd class="text-gray-600 italic text-xs">{{ $vendorOrder->order->order_note }}</dd>
            </div>
            @endif
        </dl>
    </div>

    {{-- Courier snapshot (read-only) --}}
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">কুরিয়ার তথ্য</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">কুরিয়ার</dt>
                <dd class="font-medium">{{ $vendorOrder->courier_name ?: '—' }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">ট্র্যাকিং</dt>
                <dd class="font-mono text-xs">{{ $vendorOrder->tracking_number ?: '—' }}</dd>
            </div>
            @if($vendorOrder->ready_at)
            <div class="flex justify-between">
                <dt class="text-gray-500">প্রস্তুত</dt>
                <dd class="text-xs">{{ $vendorOrder->ready_at->format('d M Y, h:i A') }}</dd>
            </div>
            @endif
            @if($vendorOrder->handed_to_courier_at)
            <div class="flex justify-between">
                <dt class="text-gray-500">কুরিয়ারে দেওয়া</dt>
                <dd class="text-xs">{{ $vendorOrder->handed_to_courier_at->format('d M Y, h:i A') }}</dd>
            </div>
            @endif
            <div class="flex justify-between">
                <dt class="text-gray-500">অর্ডার অবস্থা</dt>
                <dd class="font-medium">{{ $vendorOrder->order?->order_status }}</dd>
            </div>
        </dl>
    </div>
</div>

{{-- Ordered items --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden mb-5">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">আমার পণ্যসমূহ (এই অর্ডারে)</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পণ্য / ভেরিয়েন্ট</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">ধরন</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পরিমাণ</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">একক মূল্য</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">মোট</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($vendorOrder->order->items as $item)
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $item->product_name }}</p>
                    @if($item->variant_name)
                    <p class="text-xs text-gray-400">{{ $item->variant_name }}</p>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-1.5 py-0.5 rounded {{ $item->sell_type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                        {{ $item->sell_type === 'retail' ? 'খুচরা' : 'পাইকারি' }}
                    </span>
                    @if($item->price_label)
                    <span class="text-xs text-gray-400 ml-1">{{ $item->price_label }}</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    {{ $item->quantity_gram >= 1000
                        ? ($item->quantity_gram / 1000) . ' কেজি'
                        : $item->quantity_gram . ' গ্রাম' }}
                </td>
                <td class="px-4 py-3 text-right font-mono text-sm">৳{{ number_format($item->unit_price, 2) }}</td>
                <td class="px-4 py-3 text-right font-mono font-semibold">৳{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t bg-gray-50">
            <tr>
                <td colspan="4" class="px-4 py-2 text-right text-sm text-gray-600 font-medium">সাবটোটাল</td>
                <td class="px-4 py-2 text-right font-mono font-bold text-gray-800">৳{{ number_format($vendorOrder->subtotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

{{-- Fulfillment update form --}}
<div class="bg-white rounded-xl border border-indigo-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-indigo-100 bg-indigo-50">
        <h3 class="font-semibold text-indigo-800 text-sm">ফুলফিলমেন্ট আপডেট</h3>
        <p class="text-xs text-indigo-500 mt-0.5">শুধুমাত্র আপনার প্যাকিং ও হ্যান্ডওভার স্ট্যাটাস আপডেট করুন। ডেলিভারি চার্জ বা চূড়ান্ত অর্ডার স্ট্যাটাস অ্যাডমিন নিয়ন্ত্রণ করেন।</p>
    </div>

    <form method="POST" action="{{ route('vendor.orders.fulfillment', $vendorOrder) }}" class="p-5">
        @csrf
        @method('PATCH')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Fulfillment status --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ফুলফিলমেন্ট স্ট্যাটাস <span class="text-red-500">*</span></label>
                <select name="fulfillment_status"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    @foreach($fulfillmentStatuses as $key => $label)
                    <option value="{{ $key }}" {{ $vendorOrder->fulfillment_status === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- Courier select --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">কুরিয়ার (ঐচ্ছিক)</label>
                <select name="courier_id"
                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">— কুরিয়ার বেছে নিন —</option>
                    @foreach($couriers as $courier)
                    <option value="{{ $courier->id }}" {{ $vendorOrder->courier_id == $courier->id ? 'selected' : '' }}>
                        {{ $courier->name }}
                    </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">শুধুমাত্র অ্যাডমিন-অনুমোদিত কুরিয়ার দেখাচ্ছে।</p>
            </div>

            {{-- Tracking number --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ট্র্যাকিং নম্বর</label>
                <input type="text" name="tracking_number"
                       value="{{ old('tracking_number', $vendorOrder->tracking_number) }}"
                       placeholder="যেমন: SF-12345678"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>

            {{-- Vendor note --}}
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">ভেন্ডর নোট</label>
                <input type="text" name="vendor_note"
                       value="{{ old('vendor_note', $vendorOrder->vendor_note) }}"
                       placeholder="প্যাকিং বা ডেলিভারি সংক্রান্ত নোট"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mt-4 flex items-center gap-3">
            <button type="submit"
                    class="bg-indigo-700 hover:bg-indigo-800 text-white text-sm font-medium px-6 py-2 rounded-lg transition-colors">
                আপডেট করুন
            </button>
            <a href="{{ route('vendor.orders.index') }}" class="text-sm text-gray-400 hover:text-gray-600">বাতিল</a>
        </div>
    </form>
</div>

@endsection
