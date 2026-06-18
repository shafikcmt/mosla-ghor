@extends('vendor.layouts.app')

@section('title', 'Paykari Combo Enquiry #' . $enquiry->id)

@section('content')
<div class="px-4 sm:px-6 py-8 max-w-4xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('vendor.paykari-combo.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← ফিরে যান</a>
        <span class="text-gray-300">/</span>
        <span class="font-semibold text-gray-700">Enquiry #{{ $enquiry->id }}</span>
        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $enquiry->statusBadgeClass() }}">
            {{ $enquiry->statusLabel() }}
        </span>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
        {{ session('success') }}
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Enquiry Info (no customer contact) --}}
        <div class="lg:col-span-2 space-y-5">

            <div class="bg-white rounded-2xl border border-amber-100 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Enquiry তথ্য</h3>
                <div class="grid grid-cols-2 gap-3 text-sm mb-4">
                    <div>
                        <span class="text-gray-400 text-xs">ডেলিভারি লোকেশন</span>
                        <p class="font-semibold text-gray-800">{{ $enquiry->delivery_location }}</p>
                    </div>
                    <div>
                        <span class="text-gray-400 text-xs">ব্যবসার ধরন</span>
                        <p class="font-semibold text-gray-800">{{ $enquiry->businessTypeLabel() }}</p>
                    </div>
                </div>
                @if($enquiry->message)
                <div class="bg-amber-50 rounded-xl px-4 py-3 text-sm text-amber-800">
                    <span class="text-amber-600 text-xs block mb-1">বার্তা</span>
                    {{ $enquiry->message }}
                </div>
                @endif

                {{-- Customer contact hidden --}}
                <div class="mt-4 bg-gray-50 rounded-xl px-4 py-2 text-xs text-gray-400 italic">
                    Customer contact তথ্য vendor-এর কাছে প্রদর্শিত হচ্ছে না।
                    Quote approve হলে admin যোগাযোগ করবে।
                </div>
            </div>

            {{-- Products --}}
            <div class="bg-white rounded-2xl border border-amber-100 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">নির্বাচিত পণ্যসমূহ</h3>
                <div class="space-y-2">
                    @foreach($enquiry->items as $item)
                    <div class="flex justify-between items-center py-2 border-b border-amber-50 last:border-0 text-sm">
                        <span class="font-serif-bn font-bold text-gray-800">{{ $item->productLabel() }}</span>
                        <span class="font-bold text-amber-700">{{ $item->quantity_kg }} kg</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- My Quotes --}}
            @if($enquiry->quotes->isNotEmpty())
            <div class="bg-white rounded-2xl border border-green-200 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">আমার Quotes</h3>
                @foreach($enquiry->quotes as $quote)
                <div class="border border-gray-100 rounded-xl p-4 mb-3 last:mb-0">
                    <div class="flex justify-between mb-2">
                        <span class="text-xs text-gray-500">{{ $quote->created_at->format('d M Y') }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $quote->statusBadgeClass() }}">{{ $quote->statusLabel() }}</span>
                    </div>
                    <div class="space-y-1 text-sm">
                        @foreach($quote->items as $qitem)
                        <div class="flex justify-between text-gray-700">
                            <span>{{ $qitem['product_name'] }} ({{ $qitem['quantity_kg'] }}kg × ৳{{ number_format($qitem['unit_price'],0) }})</span>
                            <span>৳{{ number_format($qitem['subtotal'],0) }}</span>
                        </div>
                        @endforeach
                        <div class="flex justify-between font-bold text-[#14532d] pt-1 border-t">
                            <span>Grand Total</span>
                            <span>৳{{ number_format($quote->grandTotal(), 0) }}</span>
                        </div>
                    </div>
                    @if($quote->customer_response)
                    <p class="text-xs mt-2 font-semibold {{ $quote->customer_response === 'accepted' ? 'text-green-700' : 'text-red-600' }}">
                        Customer: {{ $quote->customer_response === 'accepted' ? '✓ Accepted' : '✗ Declined' }}
                    </p>
                    @endif
                    @if($quote->admin_note)
                    <p class="text-xs text-gray-400 mt-1">Admin: {{ $quote->admin_note }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

        </div>

        {{-- Sidebar: Send Quote CTA --}}
        <div>
            @if(in_array($enquiry->status, ['pending', 'quoted']))
            <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5">
                <h3 class="text-sm font-bold text-amber-800 mb-2">Quote পাঠান</h3>
                <p class="text-amber-700 text-xs mb-4 leading-relaxed">
                    প্রতিটি পণ্যের জন্য আলাদা unit price দিন।
                    Admin approve করার পরে customer দেখতে পাবে।
                </p>
                <a href="{{ route('vendor.paykari-combo.quote', $enquiry) }}"
                   class="block w-full text-center bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 rounded-xl text-sm transition-colors">
                    Quote তৈরি করুন →
                </a>
            </div>
            @else
            <div class="bg-gray-50 border border-gray-200 rounded-2xl p-5 text-center text-sm text-gray-500">
                Status: <strong>{{ $enquiry->statusLabel() }}</strong><br>
                আর quote পাঠানো যাবে না।
            </div>
            @endif
        </div>

    </div>

</div>
@endsection
