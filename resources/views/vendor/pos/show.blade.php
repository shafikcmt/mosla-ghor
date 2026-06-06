@extends('vendor.layout')
@section('title', 'বিক্রয় #' . $order->order_number)

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-800">বিক্রয় #{{ $order->order_number }}</h1>
    <div class="flex items-center gap-2">
        <a href="{{ route('vendor.pos.create') }}" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg">+ নতুন বিক্রয়</a>
        <a href="{{ route('vendor.pos.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← তালিকা</a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 p-5">
        <table class="w-full text-sm">
            <thead class="text-gray-400 text-xs uppercase border-b">
                <tr>
                    <th class="text-left py-2">পণ্য</th>
                    <th class="text-right py-2">পরিমাণ</th>
                    <th class="text-right py-2">দাম</th>
                    <th class="text-right py-2">ছাড়</th>
                    <th class="text-right py-2">মোট</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($order->items as $it)
                    <tr>
                        <td class="py-2 text-gray-800">{{ $it->product_name }}</td>
                        <td class="py-2 text-right text-gray-600">{{ $it->quantityLabel() }}</td>
                        <td class="py-2 text-right text-gray-600">৳{{ number_format($it->unit_price, 2) }}</td>
                        <td class="py-2 text-right text-gray-600">৳{{ number_format($it->discount_amount, 2) }}</td>
                        <td class="py-2 text-right font-semibold text-gray-800">৳{{ number_format($it->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="mt-4 ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">সাবটোটাল</span><span>৳{{ number_format($order->subtotal, 2) }}</span></div>
            @if($order->discount_amount > 0)
            <div class="flex justify-between"><span class="text-gray-500">ছাড়</span><span class="text-red-600">−৳{{ number_format($order->discount_amount, 2) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-base border-t pt-1"><span>সর্বমোট</span><span class="text-indigo-700">৳{{ number_format($order->grand_total, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">পরিশোধিত</span><span class="text-green-600">৳{{ number_format($order->paid_amount, 2) }}</span></div>
            <div class="flex justify-between font-semibold"><span class="text-gray-500">বাকি</span><span class="{{ $order->due_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($order->due_amount, 2) }}</span></div>
        </div>
    </div>

    <div class="space-y-5">
        <div class="bg-white rounded-xl border border-gray-100 p-5">
            <p class="text-sm font-bold text-gray-700 mb-2">কাস্টমার</p>
            <div class="text-gray-800 font-medium">{{ $order->customer_name ?: ($order->vendorCustomer?->name ?? '—') }}</div>
            @if($order->mobile_number)<div class="text-sm text-gray-500">{{ $order->mobile_number }}</div>@endif
            @if($order->full_address)<div class="text-sm text-gray-500 mt-1">{{ $order->full_address }}</div>@endif
            @if($order->vendorCustomer)
                <a href="{{ route('vendor.customers.edit', $order->vendorCustomer) }}" class="text-xs text-indigo-600 hover:underline mt-2 inline-block">কাস্টমার প্রোফাইল →</a>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-5 text-sm space-y-1">
            <p class="text-sm font-bold text-gray-700 mb-2">তথ্য</p>
            <div class="flex justify-between"><span class="text-gray-500">তারিখ</span><span>{{ $order->created_at->format('d M Y, h:i A') }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">পেমেন্ট মাধ্যম</span><span>{{ $order->payment_method }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">স্ট্যাটাস</span><span>{{ $order->order_status }}</span></div>
            @if($order->order_note)<div class="pt-2 text-gray-600 border-t">নোট: {{ $order->order_note }}</div>@endif
        </div>

        {{-- ── Collect outstanding payment ────────────────────────────── --}}
        @if($order->due_amount > 0)
        <div class="bg-amber-50 rounded-xl border border-amber-200 p-5">
            <p class="text-sm font-bold text-amber-800 mb-1">বাকি আদায়</p>
            <p class="text-sm text-amber-700 mb-3">বাকি আছে ৳{{ number_format($order->due_amount, 2) }}</p>
            <form method="POST" action="{{ route('vendor.pos.collect-payment', $order) }}" class="flex items-center gap-2">
                @csrf
                <input type="number" name="amount" step="0.01" min="0.01" max="{{ $order->due_amount }}"
                       value="{{ $order->due_amount }}" required
                       class="flex-1 border border-amber-300 rounded-lg px-3 py-2 text-sm">
                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium whitespace-nowrap">আদায় করুন</button>
            </form>
        </div>
        @endif

        {{-- ── Share / invoice links ──────────────────────────────────── --}}
        <div class="bg-white rounded-xl border border-gray-100 p-5 space-y-3" x-data="{ copied: '' }">
            <p class="text-sm font-bold text-gray-700">ইনভয়েস শেয়ার</p>

            @if($order->isInvoiceActive())
                <div class="flex items-center gap-2">
                    <input type="text" readonly value="{{ $order->invoiceUrl() }}" x-ref="invlink"
                           class="flex-1 border rounded-lg px-2 py-1.5 text-xs bg-gray-50 font-mono truncate">
                    <button type="button"
                            @click="navigator.clipboard.writeText($refs.invlink.value); copied='inv'; setTimeout(()=>copied='',1500)"
                            class="text-xs bg-gray-100 hover:bg-gray-200 px-3 py-1.5 rounded-lg whitespace-nowrap">
                        <span x-text="copied==='inv' ? 'কপি ✓' : 'কপি'"></span>
                    </button>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ $order->invoiceUrl() }}" target="_blank"
                       class="text-center text-xs bg-gray-800 hover:bg-gray-900 text-white py-2 rounded-lg">ইনভয়েস দেখুন</a>
                    @if(\App\Support\VendorSettings::vendorCanShareWhatsapp())
                    <form method="POST" action="{{ route('vendor.pos.whatsapp', $order) }}">
                        @csrf
                        <button type="submit" class="w-full text-xs bg-[#25D366] hover:bg-[#1da851] text-white py-2 rounded-lg">WhatsApp পাঠান</button>
                    </form>
                    @endif
                </div>

                @if($order->whatsapp_sent_at)
                    <p class="text-[11px] text-green-600">✓ WhatsApp পাঠানো হয়েছে — {{ $order->whatsapp_sent_at->format('d M, H:i') }}</p>
                @endif

                <form method="POST" action="{{ route('vendor.pos.invoice-toggle', $order) }}">
                    @csrf
                    <button type="submit" class="text-xs text-red-500 hover:underline">ইনভয়েস লিংক বন্ধ করুন</button>
                </form>
            @else
                <p class="text-xs text-gray-400">ইনভয়েস লিংক বন্ধ আছে।</p>
                <form method="POST" action="{{ route('vendor.pos.invoice-toggle', $order) }}">
                    @csrf
                    <button type="submit" class="text-xs text-indigo-600 hover:underline">আবার চালু করুন</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
