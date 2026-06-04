@extends('customer.layout')
@section('title', 'প্রাপ্ত কোটেশন')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">প্রাপ্ত কোটেশন</h2>

@if($quotes->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400">
    <p class="text-4xl mb-3">📋</p>
    <p class="text-sm">এখনো কোনো কোটেশন পাওয়া যায়নি।</p>
</div>
@else
<div class="space-y-4">
    @foreach($quotes as $quote)
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="font-semibold text-gray-800">{{ $quote->enquiry?->product_name }}</p>
            <p class="text-[#14532d] font-bold text-lg mt-0.5">৳{{ number_format($quote->unit_price, 2) }}/kg · মোট ৳{{ number_format($quote->grandTotal(), 2) }}</p>
            <p class="text-gray-400 text-xs mt-0.5">{{ $quote->created_at->format('d M Y') }}</p>
        </div>
        <a href="{{ route('customer.wholesale.enquiry.show', $quote->enquiry_id) }}"
           class="text-sm bg-[#14532d] hover:bg-[#166534] text-white px-4 py-2 rounded-xl transition-colors font-semibold flex-shrink-0">
            বিস্তারিত
        </a>
    </div>
    @endforeach
</div>
@endif
@endsection
