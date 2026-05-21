@extends('customer.layout')
@section('title', 'নতুন সাপোর্ট টিকেট')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.support.index') }}" class="text-sm text-gray-500 hover:text-[#14532d]">← সাপোর্ট তালিকা</a>
    </div>
    <h1 class="text-xl font-bold text-gray-800 mb-5">নতুন সাপোর্ট টিকেট</h1>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('customer.support.store') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">সম্পর্কিত অর্ডার <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <select name="order_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <option value="">অর্ডার বেছে নিন (ঐচ্ছিক)</option>
                    @foreach($orders as $order)
                    <option value="{{ $order->id }}" {{ old('order_id') == $order->id ? 'selected' : '' }}>
                        {{ $order->order_number }} — ৳{{ number_format($order->grand_total, 0) }}
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">বিষয় <span class="text-red-500">*</span></label>
                <input type="text" name="subject" value="{{ old('subject') }}" required maxlength="200"
                       placeholder="আপনার সমস্যার সংক্ষেপ..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">বার্তা <span class="text-red-500">*</span></label>
                <textarea name="message" required rows="5" maxlength="2000" placeholder="বিস্তারিত লিখুন..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('message') }}</textarea>
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                পাঠান
            </button>
        </form>
    </div>
</div>
@endsection
