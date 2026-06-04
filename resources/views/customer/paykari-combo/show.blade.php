@extends('customer.layouts.app')

@section('title', 'পাইকারি কম্বো Enquiry #' . $enquiry->id)

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('customer.paykari-combo.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ফিরে যান</a>
        <span class="text-gray-300">/</span>
        <span class="text-gray-600 text-sm font-semibold">Enquiry #{{ $enquiry->id }}</span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
        {{ session('success') }}
    </div>
    @endif

    {{-- Enquiry info --}}
    <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-6 mb-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-[#14532d] font-serif-bn">Enquiry বিস্তারিত</h2>
            <span class="text-xs px-3 py-1 rounded-full font-semibold {{ $enquiry->statusBadgeClass() }}">
                {{ $enquiry->statusLabel() }}
            </span>
        </div>

        <div class="grid grid-cols-2 gap-3 text-sm mb-4">
            <div>
                <span class="text-gray-500 text-xs uppercase tracking-wider">ডেলিভারি লোকেশন</span>
                <p class="font-semibold text-gray-800">{{ $enquiry->delivery_location }}</p>
            </div>
            <div>
                <span class="text-gray-500 text-xs uppercase tracking-wider">ব্যবসার ধরন</span>
                <p class="font-semibold text-gray-800">{{ $enquiry->businessTypeLabel() }}</p>
            </div>
        </div>

        @if($enquiry->message)
        <div class="bg-gray-50 rounded-xl px-4 py-3 text-sm text-gray-700 mb-4">
            <span class="text-gray-400 text-xs uppercase tracking-wider block mb-1">বার্তা</span>
            {{ $enquiry->message }}
        </div>
        @endif

        {{-- Selected products --}}
        <div>
            <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">নির্বাচিত পণ্যসমূহ</h3>
            <div class="space-y-2">
                @foreach($enquiry->items as $item)
                <div class="flex justify-between items-center py-2 border-b border-amber-50 last:border-0">
                    <span class="font-serif-bn font-semibold text-gray-800 text-sm">{{ $item->product_name }}</span>
                    <span class="text-amber-700 font-bold text-sm">{{ $item->quantity_kg }} kg</span>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Cancel --}}
        @if(in_array($enquiry->status, ['pending', 'quoted']))
        <form action="{{ route('customer.paykari-combo.cancel', $enquiry) }}" method="POST" class="mt-5 pt-4 border-t border-amber-100">
            @csrf @method('PATCH')
            <button type="submit" onclick="return confirm('Enquiry বাতিল করবেন?')"
                    class="text-red-500 hover:text-red-700 text-sm font-semibold transition-colors">
                Enquiry বাতিল করুন
            </button>
        </form>
        @endif
    </div>

    {{-- Received Quotes --}}
    @php
        $approvedQuotes = $enquiry->quotes->where('admin_approved', true)->where('status', 'approved');
    @endphp

    @if($approvedQuotes->isNotEmpty())
    <h2 class="text-lg font-bold text-[#14532d] font-serif-bn mb-4">প্রাপ্ত Quote সমূহ</h2>
    @foreach($approvedQuotes as $quote)
    <div class="bg-white rounded-2xl shadow-sm border border-green-200 p-6 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div>
                <span class="text-sm font-semibold text-gray-800">
                    {{ $quote->vendor->business_name ?? 'Supplier' }}
                </span>
                @if($quote->valid_until)
                <span class="text-xs text-gray-400 ml-2">Valid until {{ $quote->valid_until->format('d M Y') }}</span>
                @endif
            </div>
            <span class="text-xs px-2 py-1 rounded-full font-semibold {{ $quote->statusBadgeClass() }}">
                {{ $quote->statusLabel() }}
            </span>
        </div>

        {{-- Item-wise quote --}}
        <div class="space-y-2 mb-4">
            @foreach($quote->items as $qitem)
            <div class="flex justify-between items-center text-sm py-1.5 border-b border-gray-100 last:border-0">
                <span class="font-serif-bn text-gray-800 font-semibold">{{ $qitem['product_name'] }}</span>
                <div class="text-right">
                    <span class="text-gray-500 text-xs">{{ $qitem['quantity_kg'] }} kg × ৳{{ number_format($qitem['unit_price'], 0) }}</span>
                    <span class="font-bold text-[#14532d] ml-2">৳{{ number_format($qitem['subtotal'], 0) }}</span>
                </div>
            </div>
            @endforeach
        </div>

        <div class="space-y-1 text-sm border-t border-gray-100 pt-3">
            <div class="flex justify-between text-gray-600">
                <span>Item মোট</span>
                <span>৳{{ number_format($quote->itemTotal(), 0) }}</span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>ডেলিভারি চার্জ</span>
                <span>৳{{ number_format($quote->delivery_charge, 0) }}</span>
            </div>
            <div class="flex justify-between font-bold text-[#14532d] text-base pt-1 border-t border-gray-100">
                <span>মোট</span>
                <span>৳{{ number_format($quote->grandTotal(), 0) }}</span>
            </div>
        </div>

        @if($quote->advance_required)
        <div class="mt-3 bg-amber-50 rounded-xl px-4 py-2 text-sm text-amber-800">
            Advance প্রয়োজন: ৳{{ number_format($quote->advance_amount ?? 0, 0) }}
        </div>
        @endif

        @if($quote->note)
        <div class="mt-3 bg-gray-50 rounded-xl px-4 py-2 text-sm text-gray-700">
            <span class="text-gray-400 text-xs block mb-0.5">Vendor নোট</span>
            {{ $quote->note }}
        </div>
        @endif

        {{-- Customer response --}}
        @if($quote->customer_response === 'accepted')
        <div class="mt-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-green-800 text-sm font-semibold">
            ✓ আপনি এই quote গ্রহণ করেছেন।
        </div>
        @elseif($quote->customer_response === 'declined')
        <div class="mt-4 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm font-semibold">
            আপনি এই quote প্রত্যাখ্যান করেছেন।
        </div>
        @elseif(!$quote->customer_response && $enquiry->status !== 'accepted')
        <div class="mt-4 flex gap-3">
            <form action="{{ route('customer.paykari-combo.accept-quote', $enquiry) }}" method="POST">
                @csrf
                <button type="submit"
                        class="bg-[#14532d] hover:bg-[#166534] text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors">
                    Quote গ্রহণ করুন ✓
                </button>
            </form>
            <form action="{{ route('customer.paykari-combo.decline-quote', $enquiry) }}" method="POST">
                @csrf
                <button type="submit"
                        class="border border-red-300 text-red-600 hover:bg-red-50 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
                    প্রত্যাখ্যান করুন
                </button>
            </form>
        </div>
        @endif
    </div>
    @endforeach
    @elseif($enquiry->status === 'pending')
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
        <div class="text-3xl mb-3">⏳</div>
        <p class="text-amber-800 font-semibold text-sm">আপনার enquiry review করা হচ্ছে।</p>
        <p class="text-amber-600 text-xs mt-1">MoslaMart team / supplier শীঘ্রই quote পাঠাবে।</p>
    </div>
    @endif

</div>
@endsection
