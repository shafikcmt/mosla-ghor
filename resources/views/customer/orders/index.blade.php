@extends('customer.layout')
@section('title', 'আমার অর্ডার')

@section('content')
@php
$oColors = ['pending'=>'bg-yellow-100 text-yellow-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-indigo-100 text-indigo-700','shipped'=>'bg-cyan-100 text-cyan-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
$oLabels = ['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','processing'=>'প্রসেসিং','shipped'=>'কুরিয়ারে','delivered'=>'ডেলিভার্ড','cancelled'=>'বাতিল'];
$pLabels = ['cash_on_delivery'=>'ক্যাশ অন ডেলিভারি','bkash'=>'বিকাশ','nagad'=>'নগদ','rocket'=>'রকেট'];
@endphp

<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-bold text-gray-800">আমার অর্ডার</h1>
</div>

{{-- Status filter --}}
<div class="flex flex-wrap gap-2 mb-4">
    @foreach([''=>'সব', 'pending'=>'পেন্ডিং', 'confirmed'=>'নিশ্চিত', 'processing'=>'প্রসেসিং', 'shipped'=>'কুরিয়ারে', 'delivered'=>'ডেলিভার্ড', 'cancelled'=>'বাতিল'] as $val => $lbl)
    <a href="{{ route('customer.orders.index', $val ? ['status'=>$val] : []) }}"
       class="text-xs px-3 py-1.5 rounded-full border transition-colors
              {{ $status === $val || ($val === '' && !$status) ? 'bg-[#14532d] text-white border-[#14532d]' : 'border-gray-300 text-gray-600 hover:border-[#14532d]' }}">
        {{ $lbl }}
    </a>
    @endforeach
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    @forelse($orders as $order)
    <div class="px-5 py-4 border-b border-gray-50 last:border-0">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-sm font-semibold text-gray-800">{{ $order->order_number }}</span>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $oLabels[$order->order_status] ?? $order->order_status }}
                    </span>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    {{ $order->created_at->format('d M Y, h:i A') }}
                    · {{ $pLabels[$order->payment_method] ?? $order->payment_method }}
                    · ৳{{ number_format($order->grand_total, 0) }}
                </p>
                @if($order->tracking_id)
                <p class="text-xs text-gray-400 mt-0.5">ট্র্যাকিং: {{ $order->tracking_id }}</p>
                @endif
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('customer.orders.show', $order->id) }}"
                   class="text-xs bg-[#14532d] hover:bg-[#0d3520] text-white px-3 py-1.5 rounded-lg transition-colors">
                    বিস্তারিত
                </a>
                @if(in_array($order->order_status, ['pending','confirmed','processing']))
                <button onclick="document.getElementById('cancel-modal-{{ $order->id }}').classList.remove('hidden')"
                        class="text-xs border border-red-300 text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-lg transition-colors">
                    বাতিল
                </button>
                @endif
            </div>
        </div>
    </div>

    {{-- Cancel modal --}}
    @if(in_array($order->order_status, ['pending','confirmed','processing']))
    <div id="cancel-modal-{{ $order->id }}" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl">
            <h3 class="font-bold text-gray-800 mb-1">অর্ডার বাতিল করুন</h3>
            <p class="text-sm text-gray-500 mb-4">{{ $order->order_number }}</p>
            <form method="POST" action="{{ route('customer.orders.cancel', $order->id) }}">
                @csrf
                <textarea name="reason" required placeholder="বাতিলের কারণ লিখুন..." rows="3"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d] mb-3"></textarea>
                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-red-600 hover:bg-red-700 text-white text-sm py-2 rounded-lg transition-colors">বাতিল করুন</button>
                    <button type="button" onclick="document.getElementById('cancel-modal-{{ $order->id }}').classList.add('hidden')"
                            class="flex-1 border border-gray-300 text-gray-700 text-sm py-2 rounded-lg hover:bg-gray-50 transition-colors">না</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    @empty
    <div class="py-16 text-center text-gray-400">
        <p class="text-4xl mb-3">📦</p>
        <p class="text-sm">কোনো অর্ডার পাওয়া যায়নি।</p>
        <a href="/" class="mt-3 inline-block text-sm text-[#14532d] font-semibold hover:underline">পণ্য দেখুন →</a>
    </div>
    @endforelse
</div>

@if($orders->hasPages())
<div class="mt-4">{{ $orders->links() }}</div>
@endif
@endsection
