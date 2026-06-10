@extends('vendor.layout')
@section('title', 'কোটেশন ইতিহাস')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">কোটেশন ইতিহাস</h2>

@if($quotes->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📋</p>
    <p class="text-sm">এখনো কোনো কোটেশন পাঠানো হয়নি।</p>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">মূল্য/kg</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">মোট</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Admin</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($quotes as $quote)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $quote->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $quote->enquiry?->product_name }}</td>
                <td class="px-4 py-3 text-[#14532d] font-semibold hidden sm:table-cell">৳{{ number_format($quote->unit_price, 2) }}</td>
                <td class="px-4 py-3 text-[#c9a227] font-semibold hidden md:table-cell">৳{{ number_format($quote->grandTotal(), 2) }}</td>
                <td class="px-4 py-3">
                    @php
                        $rowBadge = [
                            'sent_to_customer'   => 'bg-blue-100 text-blue-700',
                            'accepted'           => 'bg-green-100 text-green-700',
                            'converted_to_order' => 'bg-green-100 text-green-700',
                            'rejected'           => 'bg-red-100 text-red-700',
                            'expired'            => 'bg-gray-100 text-gray-600',
                        ][$quote->status] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $rowBadge }}">{{ $quote->statusLabel() }}</span>
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('vendor.wholesale.quote.show', $quote->id) }}"
                       class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        দেখুন
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($quotes->hasPages())
    <div class="px-4 py-3 border-t">{{ $quotes->links() }}</div>
    @endif
</div>
@endif
@endsection
