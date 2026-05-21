@extends('customer.layout')
@section('title', 'রিটার্ন রিকোয়েস্ট')

@section('content')
<div class="max-w-lg">
    <div class="flex items-center gap-3 mb-5">
        <a href="{{ route('customer.orders.show', $order->id) }}" class="text-sm text-gray-500 hover:text-[#14532d]">← অর্ডার বিস্তারিত</a>
    </div>

    <h1 class="text-xl font-bold text-gray-800 mb-1">রিটার্ন রিকোয়েস্ট</h1>
    <p class="text-sm text-gray-500 mb-5">অর্ডার: {{ $order->order_number }}</p>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
        <form method="POST" action="{{ route('customer.returns.store') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            <input type="hidden" name="order_id" value="{{ $order->id }}">

            @if($order->items->count() > 1)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">কোন পণ্য? <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <select name="order_item_id" class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
                    <option value="">সব পণ্য</option>
                    @foreach($order->items as $item)
                    <option value="{{ $item->id }}">{{ $item->product_name }} ({{ number_format($item->quantity_gram) }} গ্রাম)</option>
                    @endforeach
                </select>
            </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">কারণ <span class="text-red-500">*</span></label>
                <input type="text" name="reason" value="{{ old('reason') }}" required maxlength="200"
                       placeholder="যেমন: ক্ষতিগ্রস্ত পণ্য, ভুল পণ্য..."
                       class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">বিস্তারিত</label>
                <textarea name="details" rows="4" maxlength="1000" placeholder="বিস্তারিত বর্ণনা করুন..."
                          class="w-full border border-gray-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-[#14532d]">{{ old('details') }}</textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ছবি <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
                <input type="file" name="image" accept="image/*"
                       class="w-full text-sm text-gray-600 border border-gray-300 rounded-lg px-3 py-2 file:mr-3 file:border-0 file:bg-gray-100 file:px-3 file:py-1 file:text-xs file:rounded">
            </div>

            <button type="submit"
                    class="w-full bg-[#14532d] hover:bg-[#0d3520] text-white font-semibold py-2.5 rounded-lg text-sm transition-colors">
                রিকোয়েস্ট পাঠান
            </button>
        </form>
    </div>
</div>
@endsection
