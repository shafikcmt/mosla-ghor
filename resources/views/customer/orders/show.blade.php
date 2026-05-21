@extends('customer.layout')
@section('title', 'অর্ডার #'.$order->order_number)

@section('content')
@php
$oColors = ['pending'=>'bg-yellow-100 text-yellow-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-indigo-100 text-indigo-700','shipped'=>'bg-cyan-100 text-cyan-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
$oLabels = ['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','processing'=>'প্রসেসিং','shipped'=>'কুরিয়ারে','delivered'=>'ডেলিভার্ড','cancelled'=>'বাতিল'];
$sellTypeLabels = ['retail'=>'রিটেইল','wholesale'=>'হোলসেল'];
@endphp

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('customer.orders.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← অর্ডার তালিকা</a>
</div>

{{-- Order Header --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-4">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-lg font-bold text-gray-800">{{ $order->order_number }}</h1>
            <p class="text-sm text-gray-500 mt-0.5">{{ $order->created_at->format('d F Y, h:i A') }}</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm px-3 py-1 rounded-full font-semibold {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $oLabels[$order->order_status] ?? $order->order_status }}
            </span>
            @if($canCancel)
            <button onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                    class="text-sm border border-red-300 text-red-600 hover:bg-red-50 px-3 py-1 rounded-lg transition-colors">
                বাতিল করুন
            </button>
            @endif
            @if($canReturn)
            <a href="{{ route('customer.returns.create', $order->id) }}"
               class="text-sm border border-orange-300 text-orange-600 hover:bg-orange-50 px-3 py-1 rounded-lg transition-colors">
                রিটার্ন করুন
            </a>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

    {{-- Left: Items + Totals --}}
    <div class="lg:col-span-2 space-y-4">

        {{-- Order Items --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-50">
                <h2 class="font-semibold text-gray-800">অর্ডার আইটেম</h2>
            </div>
            <div class="divide-y divide-gray-50">
                @foreach($order->items as $item)
                <div class="px-5 py-3 flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-800">{{ $item->product_name }}</p>
                        <div class="flex flex-wrap gap-2 mt-1">
                            @if($item->variant_name)
                            <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $item->variant_name }}</span>
                            @endif
                            <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded">{{ number_format($item->quantity_gram) }} গ্রাম</span>
                            @if($item->sell_type)
                            <span class="text-xs bg-purple-50 text-purple-600 px-2 py-0.5 rounded">{{ $sellTypeLabels[$item->sell_type] ?? $item->sell_type }}</span>
                            @endif
                            @if($item->vendor_name)
                            <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded">{{ $item->vendor_name }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="text-sm font-semibold text-gray-800">৳{{ number_format($item->line_total, 0) }}</p>
                        <p class="text-xs text-gray-400">৳{{ number_format($item->unit_price, 0) }}</p>
                    </div>
                </div>
                @endforeach
            </div>

            {{-- Price Breakdown --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-100 space-y-1.5">
                <div class="flex justify-between text-sm text-gray-600">
                    <span>সাবটোটাল</span>
                    <span>৳{{ number_format($order->subtotal, 0) }}</span>
                </div>
                @if($order->packaging_cost > 0)
                <div class="flex justify-between text-sm text-gray-600">
                    <span>প্যাকেজিং</span>
                    <span>৳{{ number_format($order->packaging_cost, 0) }}</span>
                </div>
                @endif
                <div class="flex justify-between text-sm text-gray-600">
                    <span>ডেলিভারি চার্জ</span>
                    <span>৳{{ number_format($order->delivery_charge, 0) }}</span>
                </div>
                <div class="flex justify-between text-base font-bold text-gray-800 border-t border-gray-200 pt-1.5">
                    <span>মোট</span>
                    <span>৳{{ number_format($order->grand_total, 0) }}</span>
                </div>
            </div>
        </div>

        {{-- Delivery Timeline --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 mb-4">ডেলিভারি অবস্থা</h2>
            @php
            $cancelled = $order->order_status === 'cancelled';
            $steps = [
                ['key'=>'ordered',   'label'=>'অর্ডার করা হয়েছে',       'done'=>true,                                                          'time'=>$order->created_at],
                ['key'=>'confirmed', 'label'=>'নিশ্চিত হয়েছে',           'done'=>in_array($order->order_status,['confirmed','processing','shipped','delivered']), 'time'=>null],
                ['key'=>'processing','label'=>'প্রসেসিং / প্যাকড',       'done'=>in_array($order->order_status,['processing','shipped','delivered']),             'time'=>null],
                ['key'=>'shipped',   'label'=>'কুরিয়ারে দেওয়া হয়েছে',   'done'=>($order->sent_to_courier_at || in_array($order->order_status,['shipped','delivered'])), 'time'=>$order->sent_to_courier_at],
                ['key'=>'delivered', 'label'=>'ডেলিভার্ড',               'done'=>($order->order_status==='delivered'),                          'time'=>$order->delivered_at],
            ];
            @endphp
            @if($cancelled)
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center shrink-0">
                    <span class="text-red-600 text-sm">✕</span>
                </div>
                <div>
                    <p class="text-sm font-medium text-red-600">অর্ডার বাতিল হয়েছে</p>
                    @if($order->cancellation_reason)
                    <p class="text-xs text-gray-500">কারণ: {{ $order->cancellation_reason }}</p>
                    @endif
                    @if($order->cancelled_at)
                    <p class="text-xs text-gray-400">{{ $order->cancelled_at->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            </div>
            @else
            <div class="space-y-0">
                @foreach($steps as $i => $step)
                <div class="flex gap-3">
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center shrink-0 border-2
                                    {{ $step['done'] ? 'bg-[#14532d] border-[#14532d]' : 'bg-white border-gray-300' }}">
                            @if($step['done'])
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                            @else
                            <div class="w-2 h-2 rounded-full bg-gray-300"></div>
                            @endif
                        </div>
                        @if(!$loop->last)
                        <div class="w-0.5 h-8 {{ $step['done'] ? 'bg-[#14532d]' : 'bg-gray-200' }}"></div>
                        @endif
                    </div>
                    <div class="pb-5 {{ $loop->last ? 'pb-0' : '' }}">
                        <p class="text-sm font-medium {{ $step['done'] ? 'text-gray-800' : 'text-gray-400' }}">{{ $step['label'] }}</p>
                        @if($step['time'])
                        <p class="text-xs text-gray-400">{{ $step['time']->format('d M Y, h:i A') }}</p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>

    {{-- Right: Info --}}
    <div class="space-y-4">

        {{-- Customer & Delivery --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 mb-3">ডেলিভারি তথ্য</h2>
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-gray-500">নাম</dt><dd class="text-gray-800">{{ $order->customer_name }}</dd></div>
                <div><dt class="text-xs text-gray-500">মোবাইল</dt><dd class="text-gray-800">{{ $order->mobile_number }}</dd></div>
                <div><dt class="text-xs text-gray-500">ঠিকানা</dt><dd class="text-gray-800">{{ $order->full_address }}</dd></div>
                @if($order->division_name)
                <div><dt class="text-xs text-gray-500">বিভাগ / জেলা</dt>
                    <dd class="text-gray-800">{{ implode(' / ', array_filter([$order->division_name, $order->district_name, $order->upazila_name])) }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Payment --}}
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 mb-3">পেমেন্ট</h2>
            @php
            $pLabels = ['cash_on_delivery'=>'ক্যাশ অন ডেলিভারি','bkash'=>'বিকাশ','nagad'=>'নগদ','rocket'=>'রকেট'];
            $psColors = ['pending'=>'bg-yellow-100 text-yellow-700','verified'=>'bg-green-100 text-green-700','failed'=>'bg-red-100 text-red-700'];
            $psLabels = ['pending'=>'অপেক্ষায়','verified'=>'যাচাই হয়েছে','failed'=>'ব্যর্থ'];
            @endphp
            <dl class="space-y-2 text-sm">
                <div><dt class="text-xs text-gray-500">পদ্ধতি</dt><dd>{{ $pLabels[$order->payment_method] ?? $order->payment_method }}</dd></div>
                <div><dt class="text-xs text-gray-500">পেমেন্ট স্ট্যাটাস</dt>
                    <dd><span class="text-xs px-2 py-0.5 rounded-full {{ $psColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">{{ $psLabels[$order->payment_status] ?? $order->payment_status }}</span></dd>
                </div>
                @if($order->transaction_id)
                <div><dt class="text-xs text-gray-500">ট্রানজেকশন</dt><dd class="font-mono text-xs">{{ $order->transaction_id }}</dd></div>
                @endif
            </dl>
        </div>

        {{-- Courier --}}
        @if($order->selectedCourier || $order->tracking_id)
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
            <h2 class="font-semibold text-gray-800 mb-3">কুরিয়ার তথ্য</h2>
            <dl class="space-y-2 text-sm">
                @if($order->selectedCourier)
                <div><dt class="text-xs text-gray-500">কুরিয়ার</dt><dd>{{ $order->selectedCourier->name }}</dd></div>
                @endif
                @if($order->tracking_id)
                <div><dt class="text-xs text-gray-500">ট্র্যাকিং আইডি</dt><dd class="font-mono text-xs break-all">{{ $order->tracking_id }}</dd></div>
                @endif
                @if($order->consignment_id)
                <div><dt class="text-xs text-gray-500">কনসাইনমেন্ট</dt><dd class="font-mono text-xs">{{ $order->consignment_id }}</dd></div>
                @endif
            </dl>
        </div>
        @endif

    </div>
</div>

{{-- Cancel Modal --}}
@if($canCancel)
<div id="cancel-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
        <h3 class="font-bold text-gray-800 mb-1">অর্ডার বাতিল করুন?</h3>
        <p class="text-sm text-gray-500 mb-4">{{ $order->order_number }}</p>
        <form method="POST" action="{{ route('customer.orders.cancel', $order->id) }}">
            @csrf
            <textarea name="reason" required placeholder="বাতিলের কারণ লিখুন..." rows="3"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 mb-3"></textarea>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm py-2 rounded-lg">বাতিল করুন</button>
                <button type="button" onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                        class="flex-1 border border-gray-300 text-gray-700 text-sm py-2 rounded-lg hover:bg-gray-50">না</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
