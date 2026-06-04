@extends('admin.layout')
@section('title', 'কোটেশন বিস্তারিত')

@section('content')
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
                <span class="text-xs px-3 py-1 rounded-full font-medium
                    @if($quote->admin_approved) bg-green-100 text-green-700
                    @elseif($quote->status === 'rejected') bg-red-100 text-red-700
                    @else bg-yellow-100 text-yellow-700 @endif">
                    @if($quote->admin_approved) ✓ Admin অনুমোদিত
                    @elseif($quote->status === 'rejected') Admin প্রত্যাখ্যাত
                    @else অনুমোদন অপেক্ষায়
                    @endif
                </span>
            </div>

            <dl class="grid grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">Vendor</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->vendor?->shop_name ?? $quote->vendor?->name }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->enquiry?->product_name }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">মূল্য/kg</dt><dd class="font-bold text-[#14532d] text-lg mt-0.5">৳{{ number_format($quote->unit_price, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পরিমাণ</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $quote->quantity }} {{ $quote->quantity_unit }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">সাবটোটাল</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->subtotal, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি চার্জ</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->delivery_charge, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">অগ্রিম</dt><dd class="font-semibold text-gray-800 mt-0.5">৳{{ number_format($quote->advance_required, 2) }}</dd></div>
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">মোট</dt><dd class="font-bold text-[#c9a227] text-xl mt-0.5">৳{{ number_format($quote->grandTotal(), 2) }}</dd></div>
                @if($quote->valid_until)
                <div><dt class="text-gray-400 text-xs uppercase tracking-wider">বৈধতা</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ \Carbon\Carbon::parse($quote->valid_until)->format('d M Y') }}</dd></div>
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
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Vendor নোট</p>
                {{ $quote->note }}
            </div>
            @endif
        </div>

        {{-- Commission preview --}}
        @php
            $commissionSetting = \App\Models\WholesaleCommissionSetting::resolveFor($quote->vendor_id, 'wholesale');
            $commissionAmount = $commissionSetting->calculate($quote->subtotal);
            $vendorEarning = $quote->subtotal - $commissionAmount;
        @endphp
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h3 class="text-sm font-bold text-gray-700 mb-3">কমিশন প্রিভিউ (অনুমোদনের পরে তৈরি হবে)</h3>
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
    </div>

    {{-- Right: Approve/Reject --}}
    <div class="space-y-4">
        @if(!$quote->admin_approved && $quote->status !== 'rejected')
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 text-sm mb-3">Admin কার্যক্রম</h4>
            <form action="{{ route('admin.wholesale.quote.approve', $quote->id) }}" method="POST" class="mb-3">
                @csrf
                <textarea name="admin_note" rows="2" placeholder="অনুমোদনের নোট (ঐচ্ছিক)"
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-green-500 bg-white resize-none mb-3"></textarea>
                <button type="submit"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl text-sm transition-colors">
                    ✓ কোটেশন অনুমোদন করুন
                </button>
            </form>
            <form action="{{ route('admin.wholesale.quote.reject', $quote->id) }}" method="POST">
                @csrf
                <textarea name="admin_note" rows="2" placeholder="প্রত্যাখ্যানের কারণ"
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-red-400 bg-white resize-none mb-3"></textarea>
                <button type="submit" onclick="return confirm('কোটেশন প্রত্যাখ্যান করবেন?')"
                        class="w-full border border-red-300 text-red-600 hover:bg-red-50 font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    প্রত্যাখ্যান করুন
                </button>
            </form>
        </div>
        @elseif($quote->admin_approved)
        <div class="bg-green-50 border border-green-200 rounded-2xl p-4 text-sm text-green-800">
            ✓ কোটেশন অনুমোদিত হয়েছে। Customer দেখতে পাচ্ছেন।
            @if($quote->admin_approved_at)
            <p class="text-xs mt-1 text-green-600">{{ \Carbon\Carbon::parse($quote->admin_approved_at)->format('d M Y, h:i A') }}</p>
            @endif
        </div>
        @else
        <div class="bg-red-50 border border-red-200 rounded-2xl p-4 text-sm text-red-700">
            কোটেশন প্রত্যাখ্যাত।
            @if($quote->admin_note)<p class="mt-1">{{ $quote->admin_note }}</p>@endif
        </div>
        @endif
    </div>
</div>
@endsection
