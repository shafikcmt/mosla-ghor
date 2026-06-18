@extends('customer.layout')
@section('title', 'Enquiry বিস্তারিত')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('customer.wholesale.enquiry.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry তালিকা</a>
</div>

{{-- Protected-communication policy (shown inside the logged-in enquiry area) --}}
<div class="mb-5 text-[12px] text-orange-800 bg-orange-50 border border-orange-200 rounded-xl px-4 py-3 leading-relaxed">
    আপনার তথ্য, quote এবং payment record নিরাপদ রাখার জন্য মসলা ঘর-এর chatbox এবং order process ব্যবহার করুন।
    Phone, WhatsApp, external link বা website-এর বাইরে payment/deal করা যাবে না।
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Left: Enquiry details --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Enquiry info --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">Enquiry #{{ $enquiry->id }}</h2>
                <span class="text-xs px-3 py-1 rounded-full font-medium
                    @if($enquiry->status === 'pending') bg-yellow-100 text-yellow-700
                    @elseif($enquiry->status === 'quoted') bg-blue-100 text-blue-700
                    @elseif($enquiry->status === 'accepted') bg-green-100 text-green-700
                    @elseif($enquiry->status === 'completed') bg-indigo-100 text-indigo-700
                    @else bg-red-100 text-red-700 @endif">
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
        </div>

        {{-- Quotes received --}}
        @php $visibleQuotes = $enquiry->quotes->whereIn('status', \App\Models\WholesaleQuote::CUSTOMER_VISIBLE_STATUSES); @endphp
        @if($visibleQuotes->isNotEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <h3 class="text-base font-bold text-gray-800 mb-4">প্রাপ্ত কোটেশন</h3>
            @foreach($visibleQuotes as $quote)
            <div class="border border-gray-100 rounded-xl p-4 mb-3">
                <div class="grid grid-cols-2 gap-3 text-sm mb-3">
                    <div><span class="text-gray-400 text-xs">ইউনিট মূল্য</span><div class="font-bold text-[#14532d] text-lg mt-0.5">৳{{ number_format($quote->unit_price, 2) }}</div></div>
                    <div><span class="text-gray-400 text-xs">পরিমাণ</span><div class="font-semibold text-gray-700 mt-0.5">{{ rtrim(rtrim(number_format((float)$quote->quantity,2),'0'),'.') }} {{ $quote->quantity_unit }}</div></div>
                    <div><span class="text-gray-400 text-xs">সাবটোটাল</span><div class="font-semibold text-gray-700 mt-0.5">৳{{ number_format($quote->subtotal, 2) }}</div></div>
                    <div><span class="text-gray-400 text-xs">ডেলিভারি চার্জ</span><div class="font-semibold text-gray-700 mt-0.5">৳{{ number_format($quote->delivery_charge, 2) }}</div></div>
                    <div><span class="text-gray-400 text-xs">মোট</span><div class="font-bold text-[#c9a227] text-lg mt-0.5">৳{{ number_format($quote->grandTotal(), 2) }}</div></div>
                    @if($quote->advanceAmount() > 0)
                    <div><span class="text-gray-400 text-xs">অগ্রিম</span><div class="font-semibold text-gray-700 mt-0.5">৳{{ number_format($quote->advanceAmount(), 2) }}@if($quote->advance_percentage) ({{ rtrim(rtrim(number_format($quote->advance_percentage,2),'0'),'.') }}%)@endif</div></div>
                    @endif
                    @if($quote->delivery_time)
                    <div><span class="text-gray-400 text-xs">ডেলিভারি সময়</span><div class="font-semibold text-gray-700 mt-0.5">{{ $quote->delivery_time }}</div></div>
                    @endif
                    @if($quote->valid_until)
                    <div><span class="text-gray-400 text-xs">বৈধতা</span><div class="font-semibold text-gray-700 mt-0.5">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}{{ $quote->isExpired() ? ' (মেয়াদ শেষ)' : '' }}</div></div>
                    @endif
                </div>

                @if($quote->payment_options)
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach((array)$quote->payment_options as $opt)
                    <span class="text-xs bg-blue-50 text-blue-700 border border-blue-200 px-2 py-0.5 rounded-full">{{ $opt }}</span>
                    @endforeach
                </div>
                @endif

                @if($quote->note)
                <p class="text-gray-600 text-sm bg-gray-50 rounded-xl p-3 mb-3">{{ $quote->note }}</p>
                @endif

                @if($quote->status === 'sent_to_customer' && ! $quote->isExpired())
                <div class="flex flex-wrap gap-3">
                    <form action="{{ route('customer.wholesale.quote.confirm', $quote->id) }}" method="POST"
                          onsubmit="return confirm('আপনি কি এই quote অনুযায়ী order confirm করতে চান?');">
                        @csrf
                        <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition-colors shadow">
                            ✓ Order Confirm করুন
                        </button>
                    </form>
                    <a href="{{ route('customer.wholesale.chat.show', $enquiry->id) }}"
                       class="border border-amber-300 text-amber-700 hover:bg-amber-50 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
                        প্রশ্ন করুন
                    </a>
                    <form action="{{ route('customer.wholesale.quote.reject', $quote->id) }}" method="POST"
                          onsubmit="return confirm('Quote প্রত্যাখ্যান করবেন?');">
                        @csrf
                        <button type="submit" class="border border-red-300 text-red-600 hover:bg-red-50 font-semibold px-5 py-2.5 rounded-xl text-sm transition-colors">
                            প্রত্যাখ্যান
                        </button>
                    </form>
                </div>
                @elseif($quote->status === 'converted_to_order')
                <div class="bg-green-50 border border-green-200 rounded-xl p-3 text-sm text-green-800 font-semibold flex items-center justify-between gap-3">
                    <span>✓ এই কোটেশন থেকে আপনার order তৈরি হয়েছে।</span>
                    @if($quote->order_id)
                    <a href="{{ route('customer.orders.show', $quote->order_id) }}" class="text-green-700 underline whitespace-nowrap">Order দেখুন</a>
                    @endif
                </div>
                @elseif($quote->status === 'rejected')
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-700">আপনি এই কোটেশন প্রত্যাখ্যান করেছেন।</div>
                @endif
            </div>
            @endforeach
        </div>
        @elseif(in_array($enquiry->status, ['pending', 'quoted']))
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-800">
            <p class="font-semibold">আপনার enquiry পাঠানো হয়েছে।</p>
            <p class="mt-1">Supplier/Admin quote দিলে এখানে দেখতে পাবেন এবং সরাসরি order confirm করতে পারবেন।</p>
        </div>
        @endif

        {{-- Chat link --}}
        @if(in_array($enquiry->status, ['pending', 'quoted', 'accepted']))
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-800 text-sm">Supplier-এর সাথে Chat</p>
                <p class="text-gray-400 text-xs mt-0.5">MoslaMart chatbox ব্যবহার করুন</p>
            </div>
            <a href="{{ route('customer.wholesale.chat.show', $enquiry->id) }}"
               class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors">
                Chat খুলুন →
            </a>
        </div>
        @endif
    </div>

    {{-- Right: Cancel --}}
    <div class="space-y-4">
        @if(in_array($enquiry->status, ['pending', 'quoted']))
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 text-sm mb-3">Enquiry বাতিল</h4>
            <form action="{{ route('customer.wholesale.enquiry.cancel', $enquiry->id) }}" method="POST">
                @csrf
                <button type="submit" onclick="return confirm('Enquiry বাতিল করতে চান?')"
                        class="w-full border border-red-300 text-red-600 hover:bg-red-50 font-semibold py-2 rounded-xl text-sm transition-colors">
                    Enquiry বাতিল করুন
                </button>
            </form>
        </div>
        @endif

        <div class="bg-green-50 border border-green-200 rounded-2xl p-5 text-xs text-green-800 leading-relaxed">
            আপনার অর্ডার, quote এবং payment record নিরাপদে রাখার জন্য MoslaMart-এর ভিতরেই supplier-এর সাথে chat এবং order process complete করুন।
        </div>
    </div>

</div>
@endsection
