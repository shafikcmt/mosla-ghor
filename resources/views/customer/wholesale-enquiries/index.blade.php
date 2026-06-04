@extends('customer.layout')
@section('title', 'আমার Enquiry')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">পাইকারি Enquiry</h2>
    <a href="/" class="text-sm bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl transition-colors font-semibold">
        + নতুন Enquiry
    </a>
</div>

@if($enquiries->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📋</p>
    <p class="text-sm">এখনো কোনো পাইকারি enquiry নেই।</p>
    <a href="/" class="mt-4 inline-block text-sm text-amber-600 font-semibold hover:underline">পাইকারি পণ্য দেখুন →</a>
</div>
@else
<div class="space-y-4">
    @foreach($enquiries as $enquiry)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex-1 min-w-0">
            <div class="flex flex-wrap items-center gap-2 mb-1">
                <h3 class="font-semibold text-gray-800">{{ $enquiry->product_name }}</h3>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium
                    @if($enquiry->status === 'pending') bg-yellow-100 text-yellow-700
                    @elseif($enquiry->status === 'quoted') bg-blue-100 text-blue-700
                    @elseif($enquiry->status === 'accepted') bg-green-100 text-green-700
                    @elseif($enquiry->status === 'completed') bg-indigo-100 text-indigo-700
                    @else bg-red-100 text-red-700 @endif">
                    {{ $enquiry->statusLabel() }}
                </span>
            </div>
            <p class="text-gray-500 text-sm">{{ $enquiry->quantity_kg }} kg · {{ $enquiry->delivery_location }} · {{ $enquiry->businessTypeLabel() }}</p>
            <p class="text-gray-400 text-xs mt-1">{{ $enquiry->created_at->format('d M Y') }}</p>
        </div>
        <div class="flex gap-2 flex-shrink-0">
            <a href="{{ route('customer.wholesale.enquiry.show', $enquiry->id) }}"
               class="text-sm bg-[#14532d] hover:bg-[#166534] text-white px-4 py-2 rounded-xl transition-colors font-semibold">
                বিস্তারিত
            </a>
            @if(in_array($enquiry->status, ['pending', 'quoted']))
            <a href="{{ route('customer.wholesale.chat.show', $enquiry->id) }}"
               class="text-sm border border-amber-500 text-amber-700 hover:bg-amber-50 px-4 py-2 rounded-xl transition-colors font-semibold">
                Chat
            </a>
            @endif
        </div>
    </div>
    @endforeach
</div>

@if($enquiries->hasPages())
<div class="mt-5">{{ $enquiries->links() }}</div>
@endif
@endif
@endsection
