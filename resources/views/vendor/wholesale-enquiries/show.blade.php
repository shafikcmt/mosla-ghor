@extends('vendor.layout')
@section('title', 'Enquiry বিস্তারিত')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('vendor.wholesale.enquiry.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry তালিকা</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Enquiry details (no customer contact) --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">Enquiry #{{ $enquiry->id }}</h2>
                <span class="text-xs px-3 py-1 rounded-full font-medium
                    @if($enquiry->status === 'pending') bg-yellow-100 text-yellow-700
                    @elseif($enquiry->status === 'quoted') bg-blue-100 text-blue-700
                    @elseif($enquiry->status === 'accepted') bg-green-100 text-green-700
                    @else bg-gray-100 text-gray-600 @endif">
                    {{ $enquiry->statusLabel() }}
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->productLabel() }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পরিমাণ</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি লোকেশন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->delivery_location }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ব্যবসার ধরন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->businessTypeLabel() }}</dd></div>
                @if($enquiry->message)
                <div class="col-span-2"><dt class="text-gray-400 text-xs uppercase tracking-wider">বার্তা</dt><dd class="text-gray-700 mt-0.5">{{ $enquiry->message }}</dd></div>
                @endif
            </dl>

            {{-- Privacy notice --}}
            <div class="mt-4 bg-gray-50 border border-gray-200 rounded-xl p-3 text-xs text-gray-500">
                ℹ️ Customer-এর সরাসরি যোগাযোগের তথ্য (phone/email) শুধুমাত্র Admin-এর কাছে আছে। MoslaMart chatbox ব্যবহার করুন।
            </div>
        </div>

        {{-- Quote history for this enquiry --}}
        @if($enquiry->quotes->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-800 mb-4">পাঠানো কোটেশন</h3>
            @foreach($enquiry->quotes as $quote)
            @php
                $qBadge = [
                    'sent_to_customer'   => 'bg-blue-100 text-blue-700',
                    'accepted'           => 'bg-green-100 text-green-700',
                    'converted_to_order' => 'bg-green-100 text-green-700',
                    'rejected'           => 'bg-red-100 text-red-700',
                    'expired'            => 'bg-gray-100 text-gray-600',
                ][$quote->status] ?? 'bg-gray-100 text-gray-600';
            @endphp
            <a href="{{ route('vendor.wholesale.quote.show', $quote->id) }}" class="block border border-gray-100 rounded-xl p-4 mb-3 text-sm hover:bg-gray-50 transition-colors">
                <div class="grid grid-cols-2 gap-3 mb-2">
                    <div><span class="text-gray-400 text-xs">ইউনিট মূল্য</span><div class="font-bold text-[#14532d]">৳{{ number_format($quote->unit_price, 2) }}</div></div>
                    <div><span class="text-gray-400 text-xs">মোট</span><div class="font-bold text-[#c9a227]">৳{{ number_format($quote->grandTotal(), 2) }}</div></div>
                </div>
                <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $qBadge }}">{{ $quote->statusLabel() }}</span>
            </a>
            @endforeach
        </div>
        @endif

        {{-- Chat --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-800 text-sm">Customer-এর সাথে Chat</p>
                <p class="text-gray-400 text-xs mt-0.5">MoslaMart chatbox ব্যবহার করুন</p>
            </div>
            <a href="{{ route('vendor.wholesale.chat.show', $enquiry->id) }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors">
                Chat →
            </a>
        </div>
    </div>

    {{-- Right: Actions --}}
    <div class="space-y-4">
        @if(in_array($enquiry->status, ['pending', 'quoted']))
        <a href="{{ route('vendor.wholesale.quote.create', $enquiry->id) }}"
           class="block w-full text-center bg-amber-600 hover:bg-amber-700 text-white font-bold py-3 rounded-xl text-sm transition-colors shadow">
            {{ $enquiry->status === 'quoted' ? 'আরেকটি কোটেশন পাঠান →' : 'কোটেশন পাঠান →' }}
        </a>
        @endif

        @if($enquiry->status === 'pending')
        <form action="{{ route('vendor.wholesale.enquiry.decline', $enquiry->id) }}" method="POST">
            @csrf
            <button type="submit" onclick="return confirm('Enquiry প্রত্যাখ্যান করবেন?')"
                    class="w-full border border-red-300 text-red-600 hover:bg-red-50 font-semibold py-2 rounded-xl text-sm transition-colors">
                Enquiry প্রত্যাখ্যান
            </button>
        </form>
        @endif

        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-800 leading-relaxed">
            Customer enquiry এবং quote process সুন্দরভাবে manage করার জন্য MoslaMart chatbox ব্যবহার করুন।
        </div>
    </div>
</div>
@endsection
