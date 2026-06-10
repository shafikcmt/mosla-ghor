@extends('admin.layout')
@section('title', 'কোটেশন বিস্তারিত')

@section('content')
@php
    $badge = [
        'sent_to_customer'   => 'bg-blue-100 text-blue-700',
        'accepted'           => 'bg-green-100 text-green-700',
        'converted_to_order' => 'bg-green-100 text-green-700',
        'rejected'           => 'bg-red-100 text-red-700',
        'expired'            => 'bg-gray-100 text-gray-600',
    ][$quote->status] ?? 'bg-gray-100 text-gray-600';
@endphp
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('admin.wholesale.quote.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← কোটেশন তালিকা</a>
    <span class="text-gray-300">|</span>
    <a href="{{ route('admin.wholesale.enquiry.show', $quote->enquiry_id) }}" class="text-gray-500 hover:text-gray-700 text-sm">Enquiry #{{ $quote->enquiry_id }}</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-800">কোটেশন #{{ $quote->id }}</h2>
                <span class="text-xs px-3 py-1 rounded-full font-medium {{ $badge }}">{{ $quote->statusLabel() }}</span>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">Supplier</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->vendor?->shop_name ?? $quote->vendor?->owner_name ?? 'মসলামার্ট (Admin)' }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->enquiry?->product_name }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ইউনিট মূল্য</dt><dd class="font-bold text-[#14532d] text-lg mt-0.5">৳{{ number_format($quote->unit_price, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পরিমাণ</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->quantity }} {{ $quote->quantity_unit }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">সাবটোটাল</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->subtotal, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি চার্জ</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->delivery_charge, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">অগ্রিম</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->advanceAmount(), 2) }}@if($quote->advance_percentage) ({{ rtrim(rtrim(number_format($quote->advance_percentage,2),'0'),'.') }}%)@endif</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">মোট</dt><dd class="font-bold text-[#c9a227] text-xl mt-0.5">৳{{ number_format($quote->grandTotal(), 2) }}</dd></div>
                @if($quote->delivery_time)
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি সময়</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->delivery_time }}</dd></div>
                @endif
                @if($quote->valid_until)
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">বৈধতা</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</dd></div>
                @endif
            </dl>

            @if($quote->payment_options)
            <div class="mt-4">
                <dt class="text-gray-400 text-xs uppercase tracking-wider mb-1.5">পেমেন্ট শর্ত</dt>
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
        </div>

        {{-- Commission preview --}}
        @if($quote->vendor_id)
        @php
            $commissionSetting = \App\Models\WholesaleCommissionSetting::resolveFor($quote->vendor_id, 'wholesale');
            $commissionAmount = $commissionSetting->calculate($quote->subtotal);
            $vendorEarning = $quote->subtotal - $commissionAmount;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-3">কমিশন প্রিভিউ (অর্ডার confirm হলে তৈরি হবে)</h3>
            <div class="grid grid-cols-3 gap-4 text-sm">
                <div class="text-center bg-gray-50 rounded-xl p-3">
                    <p class="text-xs text-gray-400">সাবটোটাল</p>
                    <p class="font-bold text-gray-800 mt-1">৳{{ number_format($quote->subtotal, 2) }}</p>
                </div>
                <div class="text-center bg-red-50 rounded-xl p-3">
                    <p class="text-xs text-gray-400">কমিশন ({{ $commissionSetting->commission_value }}{{ $commissionSetting->commission_type === 'percentage' ? '%' : '৳' }})</p>
                    <p class="font-bold text-red-600 mt-1">৳{{ number_format($commissionAmount, 2) }}</p>
                </div>
                <div class="text-center bg-green-50 rounded-xl p-3">
                    <p class="text-xs text-gray-400">Vendor আয়</p>
                    <p class="font-bold text-[#14532d] mt-1">৳{{ number_format($vendorEarning, 2) }}</p>
                </div>
            </div>
        </div>
        @endif
    </div>

    {{-- Right: monitoring (no approval needed) --}}
    <div class="space-y-4">
        <div class="bg-blue-50 border border-blue-200 rounded-2xl p-4 text-sm text-blue-800 leading-relaxed">
            এই কোটেশন Customer সরাসরি দেখতে পাচ্ছেন — কোনো admin approval প্রয়োজন নেই। Admin শুধু monitoring-এর জন্য দেখছেন।
        </div>
        @if($quote->order_id)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-sm text-green-800">
            ✓ এই কোটেশন থেকে অর্ডার তৈরি হয়েছে।
        </div>
        @endif
    </div>
</div>
@endsection
