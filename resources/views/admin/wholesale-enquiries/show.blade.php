@extends('admin.layout')
@section('title', 'Enquiry বিস্তারিত')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('admin.wholesale.enquiry.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Enquiry তালিকা</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Full enquiry details with customer contact --}}
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

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 text-sm">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 space-y-2">
                    <p class="text-xs font-bold text-blue-700 uppercase tracking-wider mb-2">Customer তথ্য (Admin Only)</p>
                    <div><span class="text-gray-500 text-xs">নাম:</span> <span class="font-semibold text-gray-800">{{ $enquiry->customer_name }}</span></div>
                    <div><span class="text-gray-500 text-xs">Phone:</span> <span class="font-semibold text-gray-800">{{ $enquiry->customer_phone }}</span></div>
                    @if($enquiry->customer_whatsapp)
                    <div><span class="text-gray-500 text-xs">WhatsApp:</span> <span class="font-semibold text-gray-800">{{ $enquiry->customer_whatsapp }}</span></div>
                    @endif
                    <div><span class="text-gray-500 text-xs">Email:</span> <span class="font-semibold text-gray-800">{{ $enquiry->customer?->email }}</span></div>
                </div>

                <div class="space-y-3">
                    <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পণ্য</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->product_name }}</dd></div>
                    <div><dt class="text-gray-400 text-xs uppercase tracking-wider">পরিমাণ</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ rtrim(rtrim(number_format((float)$enquiry->quantity_kg,2),'0'),'.') }} {{ $enquiry->quantity_unit ?: 'kg' }}</dd></div>
                    <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ডেলিভারি লোকেশন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->delivery_location }}</dd></div>
                    <div><dt class="text-gray-400 text-xs uppercase tracking-wider">ব্যবসার ধরন</dt><dd class="font-semibold text-gray-800 mt-0.5">{{ $enquiry->businessTypeLabel() }}</dd></div>
                </div>
            </div>

            @if($enquiry->message)
            <div class="mt-4 bg-gray-50 rounded-xl p-3 text-sm text-gray-700">
                <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">বার্তা</p>
                {{ $enquiry->message }}
            </div>
            @endif
        </div>

        {{-- Quotes --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-gray-800">Quote সমূহ</h3>
                @if(in_array($enquiry->status, ['pending', 'quoted']))
                <a href="{{ route('admin.wholesale.quote.create', $enquiry->id) }}"
                   class="text-xs bg-amber-600 hover:bg-amber-700 text-white px-4 py-2 rounded-xl font-bold transition-colors">
                    + কোটেশন পাঠান
                </a>
                @endif
            </div>
            @forelse($enquiry->quotes as $quote)
            @php
                $qBadge = [
                    'sent_to_customer'   => 'bg-blue-100 text-blue-700',
                    'accepted'           => 'bg-green-100 text-green-700',
                    'converted_to_order' => 'bg-green-100 text-green-700',
                    'rejected'           => 'bg-red-100 text-red-700',
                    'expired'            => 'bg-gray-100 text-gray-600',
                ][$quote->status] ?? 'bg-gray-100 text-gray-600';
            @endphp
            <div class="border border-gray-100 rounded-xl p-4 mb-3 flex items-center justify-between gap-4">
                <div>
                    <p class="text-sm font-bold text-[#14532d]">৳{{ number_format($quote->unit_price, 2) }}/{{ $quote->quantity_unit }} · মোট ৳{{ number_format($quote->grandTotal(), 2) }}</p>
                    <p class="text-xs text-gray-400 mt-0.5">{{ $quote->vendor?->shop_name ?? 'মসলামার্ট (Admin)' }} · {{ $quote->created_at->format('d M Y') }}</p>
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium mt-1 inline-block {{ $qBadge }}">{{ $quote->statusLabel() }}</span>
                </div>
                <a href="{{ route('admin.wholesale.quote.show', $quote->id) }}"
                   class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-medium flex-shrink-0">
                    দেখুন
                </a>
            </div>
            @empty
            <p class="text-sm text-gray-400">এখনো কোনো কোটেশন পাঠানো হয়নি।</p>
            @endforelse
        </div>

        {{-- Chat monitor link --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center justify-between">
            <div>
                <p class="font-semibold text-gray-800 text-sm">Chat Monitor</p>
                <p class="text-gray-400 text-xs mt-0.5">এই enquiry-র সকল বার্তা দেখুন ও পাঠান</p>
            </div>
            <a href="{{ route('admin.wholesale.chat.show', $enquiry->id) }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-5 py-2 rounded-xl text-sm transition-colors">
                Chat →
            </a>
        </div>
    </div>

    {{-- Right: Status change --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <h4 class="font-semibold text-gray-700 text-sm mb-3">Status পরিবর্তন</h4>
            <form action="{{ route('admin.wholesale.enquiry.status', $enquiry->id) }}" method="POST" class="space-y-3">
                @csrf
                <select name="status" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    @foreach(['pending' => 'অপেক্ষায়', 'quoted' => 'Quote পাঠানো', 'accepted' => 'গৃহীত', 'completed' => 'সম্পন্ন', 'rejected' => 'প্রত্যাখ্যাত', 'cancelled' => 'বাতিল'] as $s => $lbl)
                    <option value="{{ $s }}" {{ $enquiry->status === $s ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
                <textarea name="admin_note" rows="2" placeholder="Admin নোট (ঐচ্ছিক)"
                          class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white resize-none">{{ $enquiry->admin_note }}</textarea>
                <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl text-sm transition-colors">
                    আপডেট করুন
                </button>
            </form>
        </div>

        {{-- Assign / reassign supplier --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 text-sm">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-2">Supplier অ্যাসাইন</p>
            <p class="font-semibold text-gray-800 mb-3">{{ $enquiry->vendor?->shop_name ?? $enquiry->vendor?->owner_name ?? 'কোনো supplier নেই (Admin handle করছে)' }}</p>
            <form action="{{ route('admin.wholesale.enquiry.assign', $enquiry->id) }}" method="POST" class="space-y-2">
                @csrf
                <select name="vendor_id" class="w-full border border-gray-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                    <option value="">— Admin (কোনো supplier নয়) —</option>
                    @foreach($vendors as $v)
                    <option value="{{ $v->id }}" {{ (int)$enquiry->vendor_id === $v->id ? 'selected' : '' }}>{{ $v->shop_name ?? $v->owner_name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="w-full border border-indigo-300 text-indigo-600 hover:bg-indigo-50 font-semibold py-2 rounded-xl text-sm transition-colors">
                    অ্যাসাইন করুন
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
