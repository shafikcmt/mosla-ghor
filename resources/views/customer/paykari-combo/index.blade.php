@extends('customer.layouts.app')

@section('title', 'পাইকারি কম্বো Enquiry')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#14532d] font-serif-bn">পাইকারি কম্বো Enquiry</h1>
            <p class="text-gray-500 text-sm mt-1">আপনার সকল পাইকারি কম্বো enquiry এখানে দেখুন।</p>
        </div>
        <a href="{{ url('/') }}#combo-builder"
           class="inline-flex items-center gap-2 bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-xl text-sm transition-colors">
            + নতুন Enquiry
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl mb-5 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @if($enquiries->isEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-12 text-center">
        <div class="text-5xl mb-4">📦</div>
        <h3 class="text-lg font-bold text-gray-700 mb-2">কোনো enquiry নেই</h3>
        <p class="text-gray-500 text-sm mb-6">হোম পেজ থেকে পাইকারি কম্বো section-এ গিয়ে enquiry পাঠান।</p>
        <a href="{{ url('/') }}#combo-builder"
           class="inline-block bg-amber-600 text-white font-semibold px-6 py-2.5 rounded-xl text-sm hover:bg-amber-700 transition-colors">
            Enquiry পাঠান
        </a>
    </div>
    @else
    <div class="space-y-4">
        @foreach($enquiries as $enquiry)
        <div class="bg-white rounded-2xl shadow-sm border border-amber-100 p-5">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-xs font-mono text-gray-400">#{{ $enquiry->id }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full font-semibold {{ $enquiry->statusBadgeClass() }}">
                            {{ $enquiry->statusLabel() }}
                        </span>
                        <span class="text-xs text-gray-400">{{ $enquiry->created_at->diffForHumans() }}</span>
                    </div>

                    {{-- Products --}}
                    <div class="text-sm text-gray-700 font-medium mb-1">
                        @foreach($enquiry->items->take(3) as $item)
                            <span class="font-serif-bn">{{ $item->product_name }}</span>
                            <span class="text-amber-600">{{ $item->quantity_kg }} kg</span>
                            @if(!$loop->last)<span class="text-gray-300 mx-1">·</span>@endif
                        @endforeach
                        @if($enquiry->items->count() > 3)
                            <span class="text-gray-400 text-xs">+ {{ $enquiry->items->count() - 3 }} আরো</span>
                        @endif
                    </div>
                    <p class="text-gray-500 text-xs">{{ $enquiry->delivery_location }} · {{ $enquiry->businessTypeLabel() }}</p>
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    @if($enquiry->latestQuote && $enquiry->latestQuote->admin_approved)
                    <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-lg font-semibold">Quote আছে</span>
                    @endif
                    <a href="{{ route('customer.paykari-combo.show', $enquiry) }}"
                       class="bg-[#14532d] hover:bg-[#166534] text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors">
                        বিস্তারিত →
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $enquiries->links() }}</div>
    @endif

</div>
@endsection
