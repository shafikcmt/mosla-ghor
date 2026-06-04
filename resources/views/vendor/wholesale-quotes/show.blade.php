@extends('vendor.layout')
@section('title', 'কোটেশন বিস্তারিত')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('vendor.wholesale.quote.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← কোটেশন তালিকা</a>
    <span class="text-gray-300">|</span>
    <a href="{{ route('vendor.wholesale.enquiry.show', $quote->enquiry_id) }}" class="text-gray-500 hover:text-gray-700 text-sm">Enquiry #{{ $quote->enquiry_id }}</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">কোটেশন #{{ $quote->id }}</h2>
                <span class="text-xs px-3 py-1 rounded-full font-medium
                    {{ $quote->admin_approved ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    {{ $quote->admin_approved ? '✓ Admin অনুমোদিত' : 'Admin অনুমোদন অপেক্ষায়' }}
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->enquiry?->product_name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">মূল্য/kg</dt>
                    <dd class="font-bold text-[#14532d] text-lg mt-0.5">৳{{ number_format($quote->unit_price, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">পরিমাণ</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->quantity }} {{ $quote->quantity_unit }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">সাবটোটাল</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->subtotal, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি চার্জ</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->delivery_charge, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">অগ্রিম</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->advance_required, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">মোট</dt>
                    <dd class="font-bold text-[#c9a227] text-xl mt-0.5">৳{{ number_format($quote->grandTotal(), 2) }}</dd>
                </div>
                @if($quote->valid_until)
                <div>
                    <dt class="text-gray-400 text-xs uppercase tracking-wider">বৈধতা পর্যন্ত</dt>
                    <dd class="font-semibold text-gray-800 mt-0.5">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</dd>
                </div>
                @endif
            </dl>

            @if($quote->payment_options)
            <div class="mt-4">
                <dt class="text-gray-400 text-xs uppercase tracking-wider mb-1.5">পেমেন্ট অপশন</dt>
                <div class="flex flex-wrap gap-2">
                    @foreach((array)$quote->payment_options as $opt)
                    <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">{{ $opt }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if($quote->note)
            <div class="mt-4 bg-gray-50 rounded-xl p-3 text-sm text-gray-700">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">নোট</p>
                {{ $quote->note }}
            </div>
            @endif

            @if($quote->admin_approved && $quote->admin_note)
            <div class="mt-4 bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-800">
                <p class="text-xs font-semibold mb-1">Admin মন্তব্য</p>
                {{ $quote->admin_note }}
            </div>
            @endif

            @if(!$quote->admin_approved && $quote->status === 'rejected')
            <div class="mt-4 bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">
                <p class="text-xs font-semibold mb-1">Admin কর্তৃক প্রত্যাখ্যাত</p>
                @if($quote->admin_note){{ $quote->admin_note }}@endif
            </div>
            @endif
        </div>

        {{-- Customer response --}}
        @if($quote->admin_approved)
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-3">Customer-এর সাড়া</h3>
            @if($quote->status === 'accepted')
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-800 font-semibold">
                ✓ Customer এই কোটেশন গ্রহণ করেছেন।
            </div>
            @elseif($quote->status === 'rejected')
            <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-700">
                Customer এই কোটেশন প্রত্যাখ্যান করেছেন।
            </div>
            @else
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                Customer-এর সাড়ার অপেক্ষায়...
            </div>
            @endif
        </div>
        @endif

    </div>

    {{-- Sidebar --}}
    <div class="space-y-4">
        @if(in_array($quote->enquiry?->status, ['pending', 'quoted', 'accepted']))
        <a href="{{ route('vendor.wholesale.chat.show', $quote->enquiry_id) }}"
           class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-xl text-sm transition-colors shadow">
            Chat →
        </a>
        @endif

        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-800 leading-relaxed">
            কোটেশন Admin অনুমোদনের পরে Customer দেখতে পাবেন। চ্যাটে আলোচনা করুন।
        </div>
    </div>
</div>
@endsection
