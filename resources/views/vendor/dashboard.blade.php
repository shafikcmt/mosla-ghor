@extends('vendor.layout')
@section('title', 'ড্যাশবোর্ড')

@section('content')

@if(!$vendor->isApproved())
<div class="mb-6 rounded-xl p-5 border
    @if($vendor->status === 'pending') bg-yellow-50 border-yellow-200
    @elseif($vendor->status === 'suspended') bg-red-50 border-red-200
    @else bg-gray-50 border-gray-200 @endif">
    <div class="flex items-start gap-3">
        <div class="text-2xl">
            @if($vendor->status === 'pending') ⏳
            @elseif($vendor->status === 'suspended') 🚫
            @else ❌ @endif
        </div>
        <div>
            <h3 class="font-semibold text-gray-800 text-sm">
                @if($vendor->status === 'pending') অনুমোদন অপেক্ষায়
                @elseif($vendor->status === 'suspended') অ্যাকাউন্ট স্থগিত
                @else অ্যাকাউন্ট প্রত্যাখ্যাত @endif
            </h3>
            <p class="text-gray-600 text-sm mt-0.5">
                @if($vendor->status === 'pending') আপনার রেজিস্ট্রেশন পর্যালোচনা করা হচ্ছে। অ্যাডমিন অনুমোদনের পর আপনি পণ্য যোগ করতে পারবেন।
                @elseif($vendor->status === 'suspended') আপনার অ্যাকাউন্ট স্থগিত করা হয়েছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন।
                @else আপনার অ্যাকাউন্ট প্রত্যাখ্যাত হয়েছে। বিস্তারিত জানতে অ্যাডমিনের সাথে যোগাযোগ করুন। @endif
            </p>
            @if($vendor->admin_note)
                <p class="text-gray-500 text-xs mt-1">অ্যাডমিন নোট: {{ $vendor->admin_note }}</p>
            @endif
        </div>
    </div>
</div>
@endif

@if($vendor->isApproved())
{{-- Stats cards --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">মোট পণ্য</p>
        <p class="text-2xl font-bold text-gray-800">{{ $stats['total_products'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">মোট অর্ডার</p>
        <p class="text-2xl font-bold text-indigo-600">{{ $stats['total_orders'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">মোট আয়</p>
        <p class="text-2xl font-bold text-green-600">৳{{ number_format($stats['total_earned'] ?? 0, 0) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">পেন্ডিং পেআউট</p>
        <p class="text-2xl font-bold text-yellow-600">৳{{ number_format($stats['pending_payout'] ?? 0, 0) }}</p>
    </div>
</div>

{{-- Quick actions --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <a href="{{ route('vendor.products.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl p-4 flex items-center gap-3 transition-colors">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        <div>
            <p class="font-semibold text-sm">নতুন পণ্য যোগ করুন</p>
            <p class="text-indigo-200 text-xs mt-0.5">খুচরা ও পাইকারি সহ</p>
        </div>
    </a>
    <a href="{{ route('vendor.combos.create') }}"
       class="bg-purple-600 hover:bg-purple-700 text-white rounded-xl p-4 flex items-center gap-3 transition-colors">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
        </svg>
        <div>
            <p class="font-semibold text-sm">নতুন কম্বো তৈরি করুন</p>
            <p class="text-purple-200 text-xs mt-0.5">খুচরা বা পাইকারি</p>
        </div>
    </a>
    <a href="{{ route('vendor.orders.index') }}"
       class="bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl p-4 flex items-center gap-3 transition-colors">
        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <div>
            <p class="font-semibold text-sm">অর্ডার দেখুন</p>
            <p class="text-emerald-200 text-xs mt-0.5">পেন্ডিং: {{ $stats['pending_orders'] ?? 0 }}</p>
        </div>
    </a>
</div>

{{-- Recent orders --}}
@if($recentOrders->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800 text-sm">সাম্প্রতিক অর্ডার</h3>
        <a href="{{ route('vendor.orders.index') }}" class="text-indigo-600 text-xs hover:underline">সব দেখুন</a>
    </div>
    <div class="divide-y divide-gray-50">
        @foreach($recentOrders as $vo)
        <div class="px-5 py-3 flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-800">{{ $vo->order?->order_number }}</p>
                <p class="text-xs text-gray-500">{{ $vo->created_at->format('d M Y') }}</p>
            </div>
            <div class="text-right">
                <p class="text-sm font-semibold text-gray-800">৳{{ number_format($vo->subtotal, 0) }}</p>
                <span class="inline-block text-[10px] px-2 py-0.5 rounded-full font-medium
                    @if($vo->status === 'paid') bg-green-100 text-green-700
                    @elseif($vo->status === 'pending') bg-yellow-100 text-yellow-700
                    @else bg-gray-100 text-gray-600 @endif">
                    {{ $vo->status }}
                </span>
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif
@endif

@endsection
