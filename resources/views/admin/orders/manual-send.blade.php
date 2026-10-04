@extends('admin.layout')

@section('title', 'ইনভয়েস পাঠান — #' . $order->order_number)

@section('content')
<div class="max-w-xl mx-auto" x-data="{ sent: {{ $order->whatsapp_sent_at ? 'true' : 'false' }}, edit: false, copied: '' }">

    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden">
        {{-- Success header --}}
        <div class="bg-green-50 px-6 py-6 text-center border-b border-green-100">
            <div class="w-14 h-14 mx-auto rounded-full bg-green-600 text-white flex items-center justify-center text-3xl">✓</div>
            <h1 class="text-lg font-bold text-gray-800 mt-3">অর্ডার সেভ হয়েছে</h1>
            <p class="text-sm text-gray-500 font-mono">#{{ $order->order_number }}</p>
        </div>

        {{-- Summary --}}
        <div class="px-6 py-4 text-sm space-y-1.5 border-b border-gray-100">
            <div class="flex justify-between"><span class="text-gray-500">কাস্টমার</span><span class="font-medium text-gray-800">{{ $order->customer_name }} · <span class="font-mono">{{ $order->mobile_number }}</span></span></div>
            <div class="flex justify-between"><span class="text-gray-500">পণ্য</span><span class="text-gray-800">{{ $order->items->count() }}টি</span></div>
            <div class="flex justify-between"><span class="text-gray-500">মোট</span><span class="font-bold text-gray-800">৳{{ number_format($order->grand_total, 0) }}</span></div>
            <div class="flex justify-between"><span class="text-gray-500">বাকি (COD)</span><span class="font-semibold {{ $order->effectiveDue() > 0 ? 'text-red-600' : 'text-green-600' }}">৳{{ number_format($order->effectiveDue(), 0) }}</span></div>
            <div class="pt-2">
                @if($registered)
                    <span class="inline-block text-xs bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">👤 রেজিস্টার্ড কাস্টমার — মেসেজে লগইন লিংক যাবে</span>
                @else
                    <span class="inline-block text-xs bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full">
                        🆕 নতুন কাস্টমার — মেসেজে লিংক যাবে, কাস্টমার নিজে {{ trim((string) $order->full_address) === '' ? 'ঠিকানা' : 'বাকি তথ্য' }} দিয়ে অ্যাকাউন্ট খুলবেন
                    </span>
                @endif
            </div>
        </div>

        {{-- Send --}}
        <form method="POST" action="{{ route('admin.orders.whatsapp', $order) }}" target="_blank" class="px-6 py-5 space-y-3" @submit="sent = true">
            @csrf
            <input type="hidden" name="phone" value="{{ $order->mobile_number }}">

            <div x-show="edit" x-cloak>
                <textarea name="message" x-ref="msg" rows="14"
                          class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm font-mono leading-relaxed focus:outline-none focus:ring-2 focus:ring-green-500">{{ $message }}</textarea>
            </div>

            <button type="submit" class="w-full bg-[#25D366] hover:bg-[#1da851] text-white text-lg font-bold py-4 rounded-xl flex items-center justify-center gap-2">
                <span class="text-2xl">✆</span>
                <span x-text="sent ? 'আবার WhatsApp এ পাঠান' : 'WhatsApp এ ইনভয়েস পাঠান'">WhatsApp এ ইনভয়েস পাঠান</span>
            </button>
            <p x-show="sent" x-cloak class="text-center text-sm text-green-700">✓ WhatsApp খুলেছে — সেখানে Send চাপুন।</p>

            <div class="flex flex-wrap justify-center gap-2 text-xs">
                <button type="button" @click="edit = !edit" class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                        x-text="edit ? 'মেসেজ লুকান' : '✏️ মেসেজ দেখুন / এডিট'"></button>
                <button type="button" @click="navigator.clipboard.writeText($refs.msg.value).then(() => { copied = 'msg'; setTimeout(() => copied = '', 1500) })"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50"
                        x-text="copied === 'msg' ? '✓ কপি হয়েছে' : '📋 মেসেজ কপি'"></button>
                <a href="{{ $order->invoiceUrl() }}" target="_blank" class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">👁 ইনভয়েস দেখুন</a>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-2 gap-3 mt-4">
        <a href="{{ route('admin.orders.create') }}" class="text-center bg-[#14532d] hover:bg-[#0d3520] text-white text-sm font-semibold py-3 rounded-xl">+ আরেকটা অর্ডার</a>
        <a href="{{ route('admin.orders.show', $order) }}" class="text-center bg-white border border-gray-200 text-gray-700 text-sm font-semibold py-3 rounded-xl hover:bg-gray-50">অর্ডার ডিটেইল →</a>
    </div>
</div>
@endsection
