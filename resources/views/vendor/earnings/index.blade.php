@extends('vendor.layout')
@section('title', 'আয় ও কমিশন সারসংক্ষেপ')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">আয় ও কমিশন সারসংক্ষেপ</h2>

{{-- Summary cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">মোট বিক্রয়</p>
        <p class="text-2xl font-bold text-gray-800">৳{{ number_format($summary['total_sales'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">পাইকারি অর্ডার থেকে</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">কমিশন কর্তন</p>
        <p class="text-2xl font-bold text-red-500">৳{{ number_format($summary['total_commission'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">MoslaMart সার্ভিস চার্জ</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">আপনার আয়</p>
        <p class="text-2xl font-bold text-[#14532d]">৳{{ number_format($summary['total_earning'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">বিক্রয় - কমিশন</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">পেমেন্ট বাকি</p>
        <p class="text-2xl font-bold text-amber-600">৳{{ number_format($summary['pending_amount'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Settlement অপেক্ষায়</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">পরিশোধিত</p>
        <p class="text-2xl font-bold text-indigo-600">৳{{ number_format($summary['settled_amount'], 2) }}</p>
        <p class="text-xs text-gray-400 mt-1">Admin কর্তৃক settle করা হয়েছে</p>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-800 leading-relaxed">
        <p class="font-semibold mb-1">কমিশন সম্পর্কে</p>
        <p>MoslaMart প্রতিটি wholesale order-এ platform সার্ভিস চার্জ (কমিশন) কর্তন করে। এই পরিমাণ Admin কর্তৃক নির্ধারিত হয় এবং settlement-এর সময় স্বয়ংক্রিয়ভাবে সমন্বয় করা হয়।</p>
    </div>
</div>

{{-- Ledger table --}}
@if($ledger->isNotEmpty())
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b">
        <h3 class="font-bold text-gray-800 text-sm">লেজার বিস্তারিত</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">অর্ডার</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বিক্রয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কমিশন</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">আয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">তারিখ</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($ledger as $entry)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-500 text-xs">
                    @if($entry->order_id)#{{ $entry->order_id }}@else —@endif
                    <span class="ml-1 text-gray-400">({{ $entry->order_type }})</span>
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800">৳{{ number_format($entry->subtotal, 2) }}</td>
                <td class="px-4 py-3 text-red-500 font-semibold">৳{{ number_format($entry->commission_amount, 2) }}</td>
                <td class="px-4 py-3 text-[#14532d] font-bold">৳{{ number_format($entry->vendor_earning, 2) }}</td>
                <td class="px-4 py-3 hidden md:table-cell">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $entry->settlement_status === 'settled' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $entry->settlement_status === 'settled' ? 'পরিশোধিত' : 'অপেক্ষায়' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-400 text-xs hidden md:table-cell">{{ $entry->created_at->format('d M Y') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($ledger->hasPages())
    <div class="px-4 py-3 border-t">{{ $ledger->links() }}</div>
    @endif
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 p-10 text-center text-gray-400 text-sm">
    এখনো কোনো লেজার এন্ট্রি নেই।
</div>
@endif
@endsection
