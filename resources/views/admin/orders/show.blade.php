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

{{-- Top bar --}}
<div class="no-print flex items-center justify-between mb-5">
    <a href="{{ route('admin.orders.index') }}" class="text-sm text-gray-500 hover:text-gray-800">← অর্ডার তালিকায় ফিরুন</a>
    <button onclick="window.print()"
            class="bg-gray-800 text-white text-sm px-4 py-2 rounded hover:bg-gray-700 transition-colors">
        Print Invoice
    </button>
</div>

{{-- Invoice wrapper --}}
<div class="print-shadow bg-white rounded shadow divide-y divide-gray-100">

    {{-- Header --}}
    <div class="px-6 py-4 flex items-start justify-between">
        <div>
            <h2 class="text-lg font-bold text-gray-800">অর্ডার #{{ $order->order_number }}</h2>
            <p class="text-xs text-gray-400 mt-0.5">{{ $order->created_at->format('d M Y, h:i A') }}</p>
        </div>
        <div class="text-right">
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
                $typeLabels = ['custom' => 'কাস্টম কম্বো', 'retail' => 'রিটেইল', 'wholesale' => 'হোলসেল'];
            @endphp
            <span class="px-2.5 py-1 rounded text-xs font-semibold {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $oLabels[$order->order_status] ?? $order->order_status }}
            </span>
            <br>
            <span class="mt-1 inline-block px-2.5 py-1 rounded text-xs font-semibold {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
            </span>
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
            <div class="col-span-2">
                <span class="text-gray-500">ঠিকানা:</span>
                <span class="ml-2 text-gray-800">{{ $order->full_address }}, {{ $order->area }}, {{ $order->district }}</span>
            </div>
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
                <span>সাবটোটাল</span>
                <span>৳ {{ number_format($order->subtotal, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>প্যাকেজিং</span>
                <span>৳ {{ number_format($order->packaging_cost, 2) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>ডেলিভারি চার্জ</span>
                <span>৳ {{ number_format($order->delivery_charge, 2) }}</span>
            </div>
            <div class="flex justify-between font-bold text-gray-800 border-t border-gray-200 pt-2 text-base">
                <span>সর্বমোট</span>
                <span>৳ {{ number_format($order->grand_total, 2) }}</span>
            </div>
        </div>
    </div>

    {{-- Payment Info --}}
    <div class="px-6 py-4">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">পেমেন্ট তথ্য</h3>
        @php
            $methodLabels = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট'];
        @endphp
        <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm">
            <div>
                <span class="text-gray-500">পেমেন্ট পদ্ধতি:</span>
                <span class="ml-2 text-gray-800 font-medium">{{ $methodLabels[$order->payment_method] ?? $order->payment_method }}</span>
            </div>
            <div>
                <span class="text-gray-500">পেমেন্ট স্ট্যাটাস:</span>
                <span class="ml-2 font-medium {{ $pColors[$order->payment_status] ?? '' }} px-2 py-0.5 rounded text-xs">
                    {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                </span>
            </div>
            <div>
                <span class="text-gray-500">অর্ডার স্ট্যাটাস:</span>
                <span class="ml-2 font-medium {{ $oColors[$order->order_status] ?? '' }} px-2 py-0.5 rounded text-xs">
                    {{ $oLabels[$order->order_status] ?? $order->order_status }}
                </span>
            </div>
            @if($order->sender_number)
            <div>
                <span class="text-gray-500">সেন্ডার নম্বর:</span>
                <span class="ml-2 text-gray-800 font-medium">{{ $order->sender_number }}</span>
            </div>
            @endif
            @if($order->transaction_id)
            <div>
                <span class="text-gray-500">ট্রানজেকশন আইডি:</span>
                <span class="ml-2 text-gray-800 font-mono font-medium">{{ $order->transaction_id }}</span>
            </div>
            @endif
            @if($order->paid_amount !== null)
            <div>
                <span class="text-gray-500">পেমেন্ট করা পরিমাণ:</span>
                <span class="ml-2 text-gray-800 font-semibold">৳ {{ number_format($order->paid_amount, 2) }}</span>
            </div>
            @endif
        </div>
    </div>

    {{-- Status Update Form --}}
    <div class="no-print px-6 py-5 bg-gray-50">
        <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-4">স্ট্যাটাস আপডেট করুন</h3>
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
