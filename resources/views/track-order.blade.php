@extends('storefront.layout')

@section('title', 'অর্ডার ট্র্যাক করুন')

@section('content')
<div class="w-full max-w-md mx-auto">
    <div class="text-center mb-8">
        <h1 class="text-xl font-bold text-gray-800">অর্ডার ট্র্যাক করুন</h1>
        <p class="text-gray-500 text-sm mt-1">অর্ডার নম্বর ও মোবাইল নম্বর দিয়ে স্ট্যাটাস জানুন</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <form method="POST" action="{{ route('track-order.submit') }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">অর্ডার নম্বর <span class="text-red-500">*</span></label>
                <input type="text" name="order_number" value="{{ old('order_number', request('order_number')) }}" required
                       placeholder="MSL-XXXXXXXX-XXXXX"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর <span class="text-red-500">*</span></label>
                <input type="text" name="phone" value="{{ old('phone', request('phone')) }}" required
                       placeholder="০১XXXXXXXXX"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                ট্র্যাক করুন
            </button>
        </form>
    </div>

    @if($searched)
    @if($order)
    @php
    $oColors = ['pending'=>'bg-yellow-100 text-yellow-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-indigo-100 text-indigo-700','shipped'=>'bg-cyan-100 text-cyan-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
    $oLabels = ['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','processing'=>'প্রসেসিং','shipped'=>'কুরিয়ারে','delivered'=>'ডেলিভার্ড','cancelled'=>'বাতিল'];
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-gray-800">{{ $order->order_number }}</h2>
                <p class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }}</p>
            </div>
            <span class="text-sm px-3 py-1 rounded-full font-semibold {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $oLabels[$order->order_status] ?? $order->order_status }}
            </span>
        </div>

        {{-- Timeline --}}
        @php
        $steps = [
            ['label'=>'অর্ডার করা হয়েছে',     'done'=>true,  'time'=>$order->created_at],
            ['label'=>'নিশ্চিত হয়েছে',         'done'=>in_array($order->order_status,['confirmed','processing','shipped','delivered']), 'time'=>null],
            ['label'=>'প্রসেসিং / প্যাকড',     'done'=>in_array($order->order_status,['processing','shipped','delivered']),             'time'=>null],
            ['label'=>'কুরিয়ারে দেওয়া হয়েছে', 'done'=>($order->sent_to_courier_at || in_array($order->order_status,['shipped','delivered'])), 'time'=>$order->sent_to_courier_at],
            ['label'=>'ডেলিভার্ড',             'done'=>$order->order_status==='delivered', 'time'=>$order->delivered_at],
        ];
        @endphp
        <div>
            @foreach($steps as $step)
            <div class="flex gap-3 {{ !$loop->last ? 'mb-1' : '' }}">
                <div class="flex flex-col items-center">
                    <div class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 border-2
                                {{ $step['done'] ? 'bg-[#14532d] border-[#14532d]' : 'bg-white border-gray-200' }}">
                        @if($step['done'])
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                        @else
                        <div class="w-2 h-2 rounded-full bg-gray-200"></div>
                        @endif
                    </div>
                    @if(!$loop->last)
                    <div class="w-0.5 h-6 {{ $step['done'] ? 'bg-[#14532d]' : 'bg-gray-100' }}"></div>
                    @endif
                </div>
                <div class="pb-3">
                    <p class="text-sm {{ $step['done'] ? 'text-gray-800 font-medium' : 'text-gray-400' }}">{{ $step['label'] }}</p>
                    @if($step['time'])
                    <p class="text-xs text-gray-400">{{ $step['time']->format('d M Y, h:i A') }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>

        @if($order->tracking_id)
        <div class="bg-gray-50 rounded-lg p-3 text-sm">
            <span class="text-gray-500">ট্র্যাকিং:</span>
            <span class="font-mono ml-1">{{ $order->tracking_id }}</span>
            @if($order->selectedCourier)
            <span class="text-gray-400 ml-2">· {{ $order->selectedCourier->name }}</span>
            @endif
        </div>
        @endif
    </div>
    @else
    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-4 rounded-xl text-sm text-center">
        অর্ডার পাওয়া যায়নি। অর্ডার নম্বর ও মোবাইল নম্বর সঠিক কিনা যাচাই করুন।
    </div>
    @endif
    @endif

    <div class="text-center mt-6 text-sm text-gray-500">
        <a href="/" class="text-[#14532d] hover:underline">← হোম পেজে ফিরুন</a>
    </div>
</div>
@endsection
