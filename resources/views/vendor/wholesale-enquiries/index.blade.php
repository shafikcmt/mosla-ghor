@extends('vendor.layout')
@section('title', 'পাইকারি Enquiry')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">পাইকারি Enquiry সমূহ</h2>
    <div class="flex gap-2">
        @foreach(['all' => 'সব', 'pending' => 'অপেক্ষায়', 'quoted' => 'কোটেশন পাঠানো', 'accepted' => 'গৃহীত'] as $s => $label)
        <a href="{{ route('vendor.wholesale.enquiry.index', $s !== 'all' ? ['status' => $s] : []) }}"
           class="text-xs px-3 py-1.5 rounded-lg border transition-colors
                  {{ request('status', 'all') === $s ? 'bg-indigo-600 text-white border-indigo-600' : 'border-gray-200 text-gray-600 hover:border-indigo-300' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

{{-- Vendor notice (friendly) --}}
<div class="mb-5 bg-indigo-50 border border-indigo-200 rounded-xl p-4 text-sm text-indigo-800">
    Customer enquiry এবং quote process সুন্দরভাবে manage করার জন্য MoslaMart chatbox ব্যবহার করুন। এতে Admin, Vendor এবং Customer—সবার জন্য order record, quote history এবং payment tracking সহজ হবে।
</div>

@if($enquiries->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📭</p>
    <p class="text-sm">এখনো কোনো enquiry আসেনি।</p>
</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">পণ্য</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">পরিমাণ</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">লোকেশন</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Quote</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বার্তা</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($enquiries as $enquiry)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $enquiry->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800">{{ $enquiry->productLabel() }}</td>
                <td class="px-4 py-3 text-gray-600 hidden sm:table-cell">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs hidden md:table-cell">{{ $enquiry->delivery_location }}</td>
                <td class="px-4 py-3 hidden lg:table-cell">
                    <span class="text-xs text-gray-600">{{ $enquiry->latestQuote?->statusLabel() ?? '—' }}</span>
                </td>
                <td class="px-4 py-3">
                    @if($enquiry->unread_count > 0)
                    <span class="text-xs bg-red-500 text-white font-bold rounded-full px-2 py-0.5">{{ $enquiry->unread_count }}</span>
                    @else
                    <span class="text-xs text-gray-300">0</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        @if($enquiry->status === 'pending') bg-yellow-100 text-yellow-700
                        @elseif($enquiry->status === 'quoted') bg-blue-100 text-blue-700
                        @elseif($enquiry->status === 'accepted') bg-green-100 text-green-700
                        @else bg-gray-100 text-gray-600 @endif">
                        {{ $enquiry->statusLabel() }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex gap-2">
                        <a href="{{ route('vendor.wholesale.enquiry.show', $enquiry->id) }}"
                           class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                            দেখুন
                        </a>
                        @if(in_array($enquiry->status, ['pending', 'quoted']))
                        <a href="{{ route('vendor.wholesale.quote.create', $enquiry->id) }}"
                           class="text-xs border border-[#c9a227] text-amber-700 hover:bg-amber-50 px-3 py-1.5 rounded-lg transition-colors font-medium">
                            Quote পাঠান
                        </a>
                        @endif
                        <a href="{{ route('vendor.wholesale.chat.show', $enquiry->id) }}"
                           class="text-xs border border-gray-200 text-gray-600 hover:bg-gray-50 px-3 py-1.5 rounded-lg transition-colors font-medium">
                            Chat
                        </a>
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($enquiries->hasPages())
    <div class="px-4 py-3 border-t">{{ $enquiries->links() }}</div>
    @endif
</div>
@endif
@endsection
