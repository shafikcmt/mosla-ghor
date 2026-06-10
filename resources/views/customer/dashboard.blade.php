@extends('customer.layout')
@section('title', 'ড্যাশবোর্ড')

@section('content')
@php
$user = Auth::user();
$oColors = ['pending'=>'bg-yellow-100 text-yellow-700','confirmed'=>'bg-blue-100 text-blue-700','processing'=>'bg-indigo-100 text-indigo-700','shipped'=>'bg-cyan-100 text-cyan-700','delivered'=>'bg-green-100 text-green-700','cancelled'=>'bg-red-100 text-red-700'];
$oLabels = ['pending'=>'পেন্ডিং','confirmed'=>'নিশ্চিত','processing'=>'প্রসেসিং','shipped'=>'কুরিয়ারে','delivered'=>'ডেলিভার্ড','cancelled'=>'বাতিল'];
@endphp

{{-- Welcome --}}
<div class="mb-5">
    <h1 class="text-xl font-bold text-gray-800">স্বাগতম, {{ $user->name }}!</h1>
    <p class="text-gray-500 text-sm mt-0.5">{{ $user->phone }}{{ $user->email ? ' · '.$user->email : '' }}</p>
</div>

{{-- Stats Cards --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
        <p class="text-2xl font-bold text-[#14532d]">{{ $stats->total ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-0.5">মোট অর্ডার</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
        <p class="text-2xl font-bold text-yellow-600">{{ $stats->pending_count ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-0.5">পেন্ডিং</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
        <p class="text-2xl font-bold text-green-600">{{ $stats->delivered_count ?? 0 }}</p>
        <p class="text-xs text-gray-500 mt-0.5">ডেলিভার্ড</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm">
        <p class="text-2xl font-bold text-[#14532d]">৳{{ number_format($stats->total_spent ?? 0, 0) }}</p>
        <p class="text-xs text-gray-500 mt-0.5">মোট খরচ</p>
    </div>
</div>

{{-- Paykari enquiry widgets --}}
@isset($enquiryStats)
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('customer.wholesale.enquiry.index') }}" class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm hover:border-amber-300 transition-colors">
        <p class="text-2xl font-bold text-amber-600">{{ $enquiryStats['my_enquiries'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">আমার Enquiry</p>
    </a>
    <a href="{{ route('customer.wholesale.quote.index') }}" class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm hover:border-blue-300 transition-colors">
        <p class="text-2xl font-bold text-blue-600">{{ $enquiryStats['quotes_received'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">প্রাপ্ত Quote</p>
    </a>
    <a href="{{ route('customer.wholesale.enquiry.index') }}" class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm hover:border-amber-300 transition-colors">
        <p class="text-2xl font-bold text-amber-700">{{ $enquiryStats['pending_confirmation'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">Confirm বাকি</p>
    </a>
    <a href="{{ route('customer.wholesale.quote.index') }}" class="bg-white rounded-xl border border-gray-100 p-4 text-center shadow-sm hover:border-green-300 transition-colors">
        <p class="text-2xl font-bold text-green-600">{{ $enquiryStats['confirmed_orders'] }}</p>
        <p class="text-xs text-gray-500 mt-0.5">Confirm অর্ডার</p>
    </a>
</div>
@endisset

{{-- Quick Actions --}}
<div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
    <a href="{{ route('customer.orders.index') }}" class="flex flex-col items-center gap-2 bg-white rounded-xl border border-gray-100 p-4 hover:border-[#14532d] transition-colors shadow-sm text-center">
        <span class="text-2xl">📦</span>
        <span class="text-xs font-medium text-gray-700">অর্ডার দেখুন</span>
    </a>
    <a href="{{ route('customer.profile.edit') }}" class="flex flex-col items-center gap-2 bg-white rounded-xl border border-gray-100 p-4 hover:border-[#14532d] transition-colors shadow-sm text-center">
        <span class="text-2xl">👤</span>
        <span class="text-xs font-medium text-gray-700">প্রোফাইল</span>
    </a>
    <a href="{{ route('customer.addresses.index') }}" class="flex flex-col items-center gap-2 bg-white rounded-xl border border-gray-100 p-4 hover:border-[#14532d] transition-colors shadow-sm text-center">
        <span class="text-2xl">📍</span>
        <span class="text-xs font-medium text-gray-700">ঠিকানা</span>
    </a>
    <a href="{{ route('customer.support.create') }}" class="flex flex-col items-center gap-2 bg-white rounded-xl border border-gray-100 p-4 hover:border-[#14532d] transition-colors shadow-sm text-center">
        <span class="text-2xl">💬</span>
        <span class="text-xs font-medium text-gray-700">সাপোর্ট</span>
    </a>
</div>

{{-- Recent Orders --}}
<div class="bg-white rounded-xl border border-gray-100 shadow-sm">
    <div class="px-5 py-4 border-b border-gray-50 flex items-center justify-between">
        <h2 class="font-bold text-gray-800">সাম্প্রতিক অর্ডার</h2>
        <a href="{{ route('customer.orders.index') }}" class="text-xs text-[#14532d] hover:underline font-medium">সব দেখুন →</a>
    </div>
    @forelse($recentOrders as $order)
    <div class="px-5 py-3 border-b border-gray-50 last:border-0 flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-sm font-medium text-gray-800">{{ $order->order_number }}</p>
            <p class="text-xs text-gray-500">{{ $order->created_at->format('d M Y') }} · ৳{{ number_format($order->grand_total, 0) }}</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                {{ $oLabels[$order->order_status] ?? $order->order_status }}
            </span>
            <a href="{{ route('customer.orders.show', $order->id) }}" class="text-xs text-[#14532d] hover:underline">দেখুন</a>
        </div>
    </div>
    @empty
    <div class="px-5 py-10 text-center text-gray-400">
        <p class="text-3xl mb-2">📦</p>
        <p class="text-sm">এখনো কোনো অর্ডার নেই।</p>
        <a href="/" class="mt-3 inline-block text-sm text-[#14532d] font-semibold hover:underline">পণ্য দেখুন →</a>
    </div>
    @endforelse
</div>
@endsection
