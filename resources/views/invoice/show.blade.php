@extends('invoice.layout')
@section('title', 'ইনভয়েস #' . $order->order_number)

@section('content')
@php
    $paymentBadge = ['paid' => ['পরিশোধিত', 'bg-green-100 text-green-700'], 'verified' => ['পরিশোধিত', 'bg-green-100 text-green-700'], 'partial' => ['আংশিক', 'bg-amber-100 text-amber-700']][$order->payment_status] ?? ['বাকি', 'bg-red-100 text-red-700'];
    // Vendor POS orders show the shop; admin orders fall back to the site's own phone.
    $shopPhone = $vendor?->phone ?: ($vendor ? null : \App\Models\WebsiteSetting::get('phone', ''));
    $shopWa = null;
    if ($shopPhone) {
        $shopWa = preg_replace('/\D/', '', $shopPhone);
        if (\Illuminate\Support\Str::startsWith($shopWa, '0')) { $shopWa = '88' . $shopWa; }
    }
@endphp

<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    {{-- Header --}}
    <div class="bg-[#1a1a1a] text-white px-6 py-5 flex items-start justify-between">
        <div>
            <div class="font-serif-bn text-[#c9a227] text-lg font-bold">{{ $vendor->shop_name ?? $siteName }}</div>
            @if($shopPhone)<div class="text-gray-300 text-xs mt-0.5">{{ $shopPhone }}</div>@endif
            @if($vendor?->address)<div class="text-gray-400 text-xs">{{ $vendor->address }}</div>@endif
        </div>
        <div class="text-right">
            <div class="text-xs text-gray-400">ইনভয়েস</div>
            <div class="font-mono text-sm">{{ $order->order_number }}</div>
            <div class="text-xs text-gray-400 mt-1">{{ $order->created_at->format('d M Y') }}</div>
        </div>
    </div>

    {{-- Customer + status --}}
    <div class="px-6 py-4 flex flex-wrap items-center justify-between gap-2 border-b border-gray-100">
        <div>
            <div class="text-xs text-gray-400">কাস্টমার</div>
            <div class="font-medium text-gray-800">{{ $order->customer_name ?: ($order->vendorCustomer?->name ?? '—') }}</div>
            @if($order->mobile_number)<div class="text-xs text-gray-500">{{ $order->mobile_number }}</div>@endif
            @if($order->full_address)<div class="text-xs text-gray-500">{{ $order->full_address }}</div>@endif
        </div>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-medium {{ $paymentBadge[1] }}">{{ $paymentBadge[0] }}</span>
    </div>

    {{-- Items --}}
    <div class="px-6 py-4">
        <table class="w-full text-sm">
            <thead class="text-gray-400 text-xs uppercase border-b">
                <tr>
                    <th class="text-left py-2">পণ্য</th>
                    <th class="text-right py-2">পরিমাণ</th>
                    <th class="text-right py-2">দাম</th>
                    <th class="text-right py-2">মোট</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach($order->items as $it)
                    <tr>
                        <td class="py-2 text-gray-800">{{ $it->product_name }}</td>
                        <td class="py-2 text-right text-gray-600">{{ $it->quantityLabel() }}</td>
                        <td class="py-2 text-right text-gray-600">৳{{ number_format($it->unit_price, 2) }}</td>
                        <td class="py-2 text-right font-semibold text-gray-800">৳{{ number_format($it->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Totals --}}
    <div class="px-6 pb-5">
        <div class="ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">সাবটোটাল</span><span>৳{{ number_format($order->subtotal, 2) }}</span></div>
            @if($order->delivery_charge > 0)
            <div class="flex justify-between"><span class="text-gray-500">ডেলিভারি চার্জ</span><span>৳{{ number_format($order->delivery_charge, 2) }}</span></div>
            @endif
            @if($order->discount_amount > 0)
            <div class="flex justify-between"><span class="text-gray-500">ছাড়</span><span class="text-red-600">−৳{{ number_format($order->discount_amount, 2) }}</span></div>
            @endif
            <div class="flex justify-between font-bold text-base border-t pt-1"><span>সর্বমোট</span><span class="text-[#0f3d22]">৳{{ number_format($order->grand_total, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">পরিশোধিত</span><span class="text-green-600">৳{{ number_format($order->effectivePaid(), 2) }}</span></div>
            <div class="flex justify-between font-semibold"><span class="text-gray-500">বাকি</span><span class="{{ $order->effectiveDue() > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($order->effectiveDue(), 2) }}</span></div>
        </div>
    </div>
</div>

{{-- Account / address prompt for customers without an account --}}
@if(! $vendor && ! $order->customerAccount() && ! auth()->check())
<a href="{{ route('invoice.account', $order->invoice_token) }}"
   class="no-print mt-5 flex items-center gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 hover:bg-amber-100">
    <span class="text-2xl">{{ trim((string) $order->full_address) === '' ? '📍' : '📝' }}</span>
    <span class="flex-1">
        <span class="block font-semibold text-gray-800 text-sm">{{ trim((string) $order->full_address) === '' ? 'ডেলিভারির জন্য আপনার ঠিকানা দিন' : 'আপনার তথ্য দিয়ে অ্যাকাউন্ট খুলুন' }}</span>
        <span class="block text-xs text-gray-500">অর্ডার ট্র্যাক ও পরে সহজে অর্ডার করতে</span>
    </span>
    <span class="text-amber-700">→</span>
</a>
@endif

{{-- Actions --}}
<div class="no-print mt-5 grid grid-cols-2 sm:grid-cols-4 gap-3">
    <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white py-2.5 rounded-lg text-sm font-medium">প্রিন্ট</button>
    @if($order->effectiveDue() > 0)
        <a href="{{ route('invoice.pay', $order->invoice_token) }}" class="bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg text-sm font-medium text-center">পেমেন্ট করুন</a>
    @endif
    <a href="{{ route('invoice.reorder', $order->invoice_token) }}" class="bg-[#0f3d22] hover:bg-[#0a2c18] text-white py-2.5 rounded-lg text-sm font-medium text-center">আবার অর্ডার</a>
    @if($shopWa)
        <a href="https://wa.me/{{ $shopWa }}?text={{ rawurlencode('ইনভয়েস #' . $order->order_number . ' প্রসঙ্গে') }}"
           target="_blank" class="bg-[#25D366] hover:bg-[#1da851] text-white py-2.5 rounded-lg text-sm font-medium text-center">দোকানে WhatsApp</a>
    @endif
</div>
@endsection
