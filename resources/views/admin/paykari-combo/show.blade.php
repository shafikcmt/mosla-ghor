@extends('admin.layouts.app')

@section('title', 'Paykari Combo Enquiry #' . $enquiry->id)

@section('content')
<div class="px-4 sm:px-6 lg:px-8 py-8 max-w-5xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.paykari-combo.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← Back</a>
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

        {{-- Left: Enquiry Details --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Customer (Admin Only) --}}
            <div class="bg-blue-50 border border-blue-200 rounded-2xl p-5">
                <h3 class="text-blue-800 text-xs font-bold uppercase tracking-wider mb-3">Customer তথ্য (Admin Only)</h3>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <span class="text-blue-600 text-xs">নাম</span>
                        <p class="font-semibold text-blue-900">{{ $enquiry->customer_name }}</p>
                    </div>
                    <div>
                        <span class="text-blue-600 text-xs">ফোন</span>
                        <p class="font-bold text-blue-900">{{ $enquiry->customer_phone }}</p>
                    </div>
                    @if($enquiry->customer_whatsapp)
                    <div>
                        <span class="text-blue-600 text-xs">WhatsApp</span>
                        <p class="font-semibold text-blue-900">{{ $enquiry->customer_whatsapp }}</p>
                    </div>
                    @endif
                    <div>
                        <span class="text-blue-600 text-xs">ব্যবসার ধরন</span>
                        <p class="font-semibold text-blue-900">{{ $enquiry->businessTypeLabel() }}</p>
                    </div>
                    <div class="col-span-2">
                        <span class="text-blue-600 text-xs">ডেলিভারি লোকেশন</span>
                        <p class="font-semibold text-blue-900">{{ $enquiry->delivery_location }}</p>
                    </div>
                </div>
                @if($enquiry->message)
                <div class="mt-3 bg-blue-100 rounded-xl px-3 py-2 text-blue-800 text-sm">{{ $enquiry->message }}</div>
                @endif
            </div>

            {{-- Products --}}
            <div class="bg-white rounded-2xl border border-amber-100 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-3">নির্বাচিত পণ্যসমূহ</h3>
                <div class="space-y-2">
                    @foreach($enquiry->items as $item)
                    <div class="flex justify-between items-center py-2 border-b border-amber-50 last:border-0 text-sm">
                        <div>
                            <span class="font-serif-bn font-bold text-gray-800">{{ $item->product_name }}</span>
                            @if($item->product)
                            <span class="text-gray-400 text-xs ml-2">{{ $item->product->name_en }}</span>
                            @endif
                        </div>
                        <span class="font-bold text-amber-700">{{ $item->quantity_kg }} kg</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Quotes --}}
            @if($enquiry->quotes->isNotEmpty())
            <div class="bg-white rounded-2xl border border-green-200 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Quotes ({{ $enquiry->quotes->count() }})</h3>
                @foreach($enquiry->quotes as $quote)
                <div class="border border-gray-200 rounded-xl p-4 mb-4 last:mb-0">
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-semibold text-gray-800">{{ $quote->vendor->business_name ?? 'Vendor' }}</span>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $quote->statusBadgeClass() }}">
                            {{ $quote->statusLabel() }}
                        </span>
                    </div>

                    <div class="space-y-1 text-sm mb-3">
                        @foreach($quote->items as $qitem)
                        <div class="flex justify-between text-gray-700">
                            <span>{{ $qitem['product_name'] }} — {{ $qitem['quantity_kg'] }}kg × ৳{{ number_format($qitem['unit_price'], 0) }}</span>
                            <span class="font-semibold">৳{{ number_format($qitem['subtotal'], 0) }}</span>
                        </div>
                        @endforeach
                        <div class="flex justify-between text-gray-500 border-t pt-1 mt-1">
                            <span>Delivery</span>
                            <span>৳{{ number_format($quote->delivery_charge, 0) }}</span>
                        </div>
                        <div class="flex justify-between font-bold text-[#14532d]">
                            <span>Grand Total</span>
                            <span>৳{{ number_format($quote->grandTotal(), 0) }}</span>
                        </div>
                    </div>

                    @if($quote->note)
                    <p class="text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2 mb-3">{{ $quote->note }}</p>
                    @endif

                    @if($quote->customer_response)
                    <p class="text-xs font-semibold {{ $quote->customer_response === 'accepted' ? 'text-green-700' : 'text-red-600' }}">
                        Customer: {{ $quote->customer_response === 'accepted' ? '✓ Accepted' : '✗ Declined' }}
                    </p>
                    @endif

                    @if($quote->status === 'pending')
                    <div class="flex gap-2 mt-3">
                        <form action="{{ route('admin.paykari-combo.quote.approve', $quote) }}" method="POST" class="inline">
                            @csrf
                            <input type="text" name="admin_note" placeholder="Admin note (optional)"
                                   class="border border-gray-200 rounded-lg px-3 py-1.5 text-xs mr-2 focus:outline-none focus:ring-1 focus:ring-green-400">
                            <button type="submit"
                                    class="bg-green-600 hover:bg-green-700 text-white font-semibold px-4 py-1.5 rounded-lg text-xs transition-colors">
                                Approve
                            </button>
                        </form>
                        <form action="{{ route('admin.paykari-combo.quote.reject', $quote) }}" method="POST" class="inline">
                            @csrf
                            <button type="submit"
                                    class="border border-red-300 text-red-600 hover:bg-red-50 font-semibold px-4 py-1.5 rounded-lg text-xs transition-colors">
                                Reject
                            </button>
                        </form>
                    </div>
                    @elseif($quote->admin_note)
                    <p class="text-xs text-gray-400 mt-2">Admin note: {{ $quote->admin_note }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

        </div>

        {{-- Right: Status + Vendor Assignment --}}
        <div class="space-y-5">

            <div class="bg-white rounded-2xl border border-gray-200 p-5">
                <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4">Status & Vendor</h3>

                <form action="{{ route('admin.paykari-combo.status', $enquiry) }}" method="POST">
                    @csrf @method('PATCH')

                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                            <select name="status"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                                @foreach(\App\Models\PaykariComboEnquiry::statuses() as $val => $label)
                                <option value="{{ $val }}" {{ $enquiry->status === $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Assign Vendor</label>
                            <select name="vendor_id"
                                    class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400">
                                <option value="">— Not assigned —</option>
                                @foreach($vendors as $vendor)
                                <option value="{{ $vendor->id }}" {{ $enquiry->vendor_id === $vendor->id ? 'selected' : '' }}>
                                    {{ $vendor->business_name }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Admin Note</label>
                            <textarea name="admin_note" rows="2"
                                      class="w-full border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"
                                      placeholder="Internal note...">{{ $enquiry->admin_note }}</textarea>
                        </div>

                        <button type="submit"
                                class="w-full bg-amber-600 hover:bg-amber-700 text-white font-bold py-2 rounded-xl text-sm transition-colors">
                            Update
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-gray-50 rounded-2xl border border-gray-200 p-5 text-xs text-gray-500 space-y-1">
                <p><span class="font-semibold">Created:</span> {{ $enquiry->created_at->format('d M Y h:i A') }}</p>
                <p><span class="font-semibold">Updated:</span> {{ $enquiry->updated_at->format('d M Y h:i A') }}</p>
                <p><span class="font-semibold">Items:</span> {{ $enquiry->items->count() }}</p>
                <p><span class="font-semibold">Quotes:</span> {{ $enquiry->quotes->count() }}</p>
            </div>

        </div>
    </div>

</div>
@endsection
