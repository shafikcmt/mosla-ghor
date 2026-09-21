@extends('invoice.layout')
@section('title', 'কোটেশন #' . $quote->id)

@section('content')
@php
    $statusBadge = [
        'sent_to_customer'   => ['কোটেশন পাঠানো হয়েছে', 'bg-blue-100 text-blue-700'],
        'accepted'           => ['গ্রহণ করা হয়েছে', 'bg-green-100 text-green-700'],
        'converted_to_order' => ['অর্ডার হয়েছে', 'bg-green-100 text-green-700'],
        'rejected'           => ['প্রত্যাখ্যাত', 'bg-red-100 text-red-700'],
        'expired'            => ['মেয়াদ শেষ', 'bg-gray-100 text-gray-600'],
    ][$quote->status] ?? [$quote->statusLabel(), 'bg-gray-100 text-gray-600'];
@endphp

<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    {{-- Header --}}
    <div class="bg-[#1a1a1a] text-white px-6 py-5 flex items-start justify-between">
        <div>
            <div class="font-serif-bn text-[#c9a227] text-lg font-bold">{{ $vendor->shop_name ?? $siteName }}</div>
            @if($vendor?->phone)<div class="text-gray-300 text-xs mt-0.5">{{ $vendor->phone }}</div>@endif
            @if($vendor?->address)<div class="text-gray-400 text-xs">{{ $vendor->address }}</div>@endif
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">কোটেশন</div>
            <div class="font-mono text-sm">#{{ $quote->id }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $quote->created_at->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Product + status --}}
    <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-2 border-b border-gray-100">
        <div>
            <div class="text-xs text-gray-400">পণ্য</div>
            <div class="font-medium text-gray-800">{{ $quote->enquiry?->productLabel() ?? $quote->enquiry?->product_name ?? '—' }}</div>
        </div>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $statusBadge[1] }}">{{ $statusBadge[0] }}</span>
    </div>

    {{-- Line --}}
    <div class="px-6 py-4">
        <table class="w-full text-sm">
            <thead class="text-gray-400 text-xs uppercase border-b">
                <tr>
                    <th class="text-left py-2">পণ্য</th>
                    <th class="text-right py-2">পরিমাণ</th>
                    <th class="text-right py-2">ইউনিট মূল্য</th>
                    <th class="text-right py-2">সাবটোটাল</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <tr>
                    <td class="py-2 text-gray-800">{{ $quote->enquiry?->productLabel() ?? $quote->enquiry?->product_name ?? '—' }}</td>
                    <td class="py-2 text-right text-gray-600">{{ rtrim(rtrim(number_format((float) $quote->quantity, 2), '0'), '.') }} {{ $quote->quantity_unit }}</td>
                    <td class="py-2 text-right text-gray-600">৳{{ number_format($quote->unit_price, 2) }}</td>
                    <td class="py-2 text-right font-semibold text-gray-800">৳{{ number_format($quote->subtotal, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="px-6 pb-5">
        <div class="ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">সাবটোটাল</span><span>৳{{ number_format($quote->subtotal, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">ডেলিভারি চার্জ</span><span>৳{{ number_format($quote->delivery_charge, 2) }}</span></div>
            @if($quote->advanceAmount() > 0)
            <div class="flex justify-between"><span class="text-gray-500">অগ্রিম@if($quote->advance_percentage) ({{ rtrim(rtrim(number_format($quote->advance_percentage, 2), '0'), '.') }}%)@endif</span><span class="text-amber-700">৳{{ number_format($quote->advanceAmount(), 2) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-base border-t pt-1"><span>সর্বমোট</span><span class="text-[#0f3d22]">৳{{ number_format($quote->grandTotal(), 2) }}</span></div>
        </div>
    </div>

    {{-- Terms --}}
    <div class="px-6 pb-5 border-t border-gray-100 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
        @if($quote->delivery_time)
        <div><div class="text-xs text-gray-400">ডেলিভারি সময়</div><div class="font-medium text-gray-800">{{ $quote->delivery_time }}</div></div>
        @endif
        @if($quote->valid_until)
        <div><div class="text-xs text-gray-400">বৈধতা</div><div class="font-medium text-gray-800">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</div></div>
        @endif
        @if($quote->payment_options)
        <div class="sm:col-span-2">
            <div class="text-xs text-gray-400 mb-1">পেমেন্ট শর্ত</div>
            <div class="flex flex-wrap gap-2">
                @foreach((array) $quote->payment_options as $opt)
                <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">{{ $opt }}</span>
                @endforeach
            </div>
        </div>
        @endif
        @if($quote->note)
        <div class="sm:col-span-2 bg-gray-50 rounded-xl p-3 text-gray-700">
            <div class="text-xs text-gray-400 uppercase tracking-wider mb-1">নোট</div>
            {{ $quote->note }}
        </div>
        @endif
    </div>
</div>

{{-- Actions --}}
<div class="no-print mt-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
    <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white py-2.5 rounded-lg text-sm font-medium">প্রিন্ট</button>
    <a href="{{ route('customer.login.otp') }}" class="bg-[#0f3d22] hover:bg-[#0a2c18] text-white py-2.5 rounded-lg text-sm font-medium text-center">OTP দিয়ে লগইন করুন</a>
</div>
<p class="no-print mt-3 text-center text-xs text-gray-400">অর্ডার confirm করতে লগইন করুন — একই ফোন নম্বরে OTP পাবেন।</p>
@endsection
