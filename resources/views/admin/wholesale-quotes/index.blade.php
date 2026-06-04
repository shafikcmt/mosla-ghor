@extends('admin.layout')
@section('title', 'কোটেশন অনুমোদন')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">কোটেশন অনুমোদন</h2>
    <div class="flex gap-2">
        @foreach(['all' => 'সব', 'pending' => 'অনুমোদন প্রয়োজন', 'approved' => 'অনুমোদিত', 'rejected' => 'প্রত্যাখ্যাত'] as $s => $label)
        <a href="{{ route('admin.wholesale.quote.index', $s !== 'all' ? ['status' => $s] : []) }}"
           class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                  {{ request('status', 'all') === $s ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:border-indigo-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

@if($quotes->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📋</p>
    <p class="text-sm">কোনো কোটেশন পাওয়া যায়নি।</p>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">মূল্য/kg</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">মোট</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Admin</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($quotes as $quote)
            <tr class="hover:bg-gray-50 {{ !$quote->admin_approved && $quote->status === 'pending' ? 'bg-yellow-50' : '' }}">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $quote->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $quote->enquiry?->product_name }}</td>
                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ $quote->vendor?->shop_name ?? $quote->vendor?->name }}</td>
                <td class="px-4 py-3 text-[#14532d] font-semibold">৳{{ number_format($quote->unit_price, 2) }}</td>
                <td class="px-4 py-3 text-[#c9a227] font-semibold hidden md:table-cell">৳{{ number_format($quote->grandTotal(), 2) }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        @if($quote->admin_approved) bg-green-100 text-green-700
                        @elseif($quote->status === 'rejected') bg-red-100 text-red-700
                        @else bg-yellow-100 text-yellow-700 @endif">
                        @if($quote->admin_approved) ✓ অনুমোদিত
                        @elseif($quote->status === 'rejected') প্রত্যাখ্যাত
                        @else অপেক্ষায়
                        @endif
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.wholesale.quote.show', $quote->id) }}"
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
