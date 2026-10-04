@extends('invoice.layout')
@section('title', 'আপনার তথ্য দিন')

@section('content')
@php $needsAddress = trim((string) $order->full_address) === ''; @endphp

<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
        <h1 class="text-lg font-bold text-gray-800">
            {{ $needsAddress ? '📍 ডেলিভারির জন্য আপনার ঠিকানা দিন' : '✅ আপনার অর্ডার কনফার্ম করুন' }}
        </h1>
        <p class="text-sm text-gray-500 mt-1">তথ্য দিয়ে একটি পাসওয়ার্ড সেট করুন — আপনার অ্যাকাউন্ট তৈরি হবে, পরে যেকোনো সময় অর্ডার ট্র্যাক ও আবার অর্ডার করতে পারবেন।</p>
    </div>

    {{-- Order summary --}}
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-100 text-sm">
        <div class="flex justify-between mb-2">
            <span class="text-gray-500">অর্ডার</span>
            <span class="font-mono font-semibold">#{{ $order->order_number }}</span>
        </div>
        @foreach($order->items as $it)
            <div class="flex justify-between text-gray-700">
                <span>{{ $it->product_name }} <span class="text-gray-400">× {{ $it->quantityLabel() }}</span></span>
                <span>৳{{ number_format($it->line_total, 0) }}</span>
            </div>
        @endforeach
        <div class="flex justify-between font-bold text-gray-800 border-t mt-2 pt-2">
            <span>মোট</span><span>৳{{ number_format($order->grand_total, 0) }}</span>
        </div>
        @if($order->effectiveDue() > 0)
        <div class="flex justify-between text-red-600 text-xs mt-0.5">
            <span>ডেলিভারিতে দিতে হবে</span><span>৳{{ number_format($order->effectiveDue(), 0) }}</span>
        </div>
        @endif
    </div>

    <form method="POST" action="{{ route('invoice.account.store', $order->invoice_token) }}" class="px-6 py-5 space-y-4">
        @csrf

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
                @foreach($errors->all() as $e)<div>• {{ $e }}</div>@endforeach
            </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">মোবাইল নম্বর</label>
            <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2.5 text-base font-mono text-gray-600">{{ $phone }}</div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">আপনার নাম <span class="text-red-500">*</span></label>
            <input type="text" name="name" required maxlength="100"
                   value="{{ old('name', $order->customer_name === 'কাস্টমার' ? '' : $order->customer_name) }}"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পূর্ণ ঠিকানা (বাড়ি, রোড, এলাকা) <span class="text-red-500">*</span></label>
            <textarea name="full_address" rows="2" required maxlength="1000"
                      class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('full_address', $order->full_address) }}</textarea>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">জেলা</label>
                <input type="text" name="district" maxlength="80" value="{{ old('district', $order->district) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">থানা / এলাকা</label>
                <input type="text" name="area" maxlength="80" value="{{ old('area', $order->area) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">বিকল্প নম্বর</label>
                <input type="tel" name="alternative_number" maxlength="20" value="{{ old('alternative_number', $order->alternative_number) }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ইমেইল (ঐচ্ছিক)</label>
                <input type="email" name="email" maxlength="150" value="{{ old('email') }}"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
        </div>
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">পাসওয়ার্ড <span class="text-red-500">*</span></label>
                <input type="password" name="password" required minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">আবার দিন <span class="text-red-500">*</span></label>
                <input type="password" name="password_confirmation" required minlength="6" autocomplete="new-password"
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-base focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>
        </div>

        <button type="submit" class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-bold py-3 rounded-lg">
            তথ্য সেভ করুন ও অ্যাকাউন্ট খুলুন
        </button>
        <p class="text-center text-xs text-gray-400">
            <a href="{{ route('invoice.show', $order->invoice_token) }}" class="underline">শুধু ইনভয়েস দেখুন</a>
        </p>
    </form>
</div>
@endsection
