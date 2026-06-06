@extends('invoice.layout')
@section('title', 'পেমেন্ট')

@section('content')
<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h1 class="text-lg font-bold text-gray-800 mb-1">পেমেন্ট তথ্য দিন</h1>
    <p class="text-sm text-gray-500 mb-4">ইনভয়েস #{{ $order->order_number }} — বাকি
        <span class="font-bold text-red-600">৳{{ number_format($order->due_amount, 2) }}</span></p>

    @if($errors->any())
        <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg text-sm">
            <ul class="list-disc list-inside">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('invoice.pay.store', $order->invoice_token) }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পেমেন্ট মাধ্যম <span class="text-red-500">*</span></label>
            <select name="payment_method" required class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                <option value="bkash">বিকাশ</option>
                <option value="nagad">নগদ</option>
                <option value="rocket">রকেট</option>
                <option value="bank">ব্যাংক ট্রান্সফার</option>
                <option value="cash">নগদ অর্থ</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">যে নম্বর থেকে পাঠিয়েছেন</label>
            <input type="text" name="sender_number" value="{{ old('sender_number') }}" placeholder="01XXXXXXXXX"
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">ট্রানজেকশন আইডি</label>
            <input type="text" name="transaction_id" value="{{ old('transaction_id') }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পরিমাণ (৳)</label>
            <input type="number" name="amount" step="0.01" min="0" value="{{ old('amount', $order->due_amount) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm">
        </div>
        <div class="flex items-center gap-3 pt-1">
            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2.5 rounded-lg text-sm font-bold">পেমেন্ট জানান</button>
            <a href="{{ route('invoice.show', $order->invoice_token) }}" class="text-sm text-gray-500 hover:text-gray-700">← ইনভয়েস</a>
        </div>
        <p class="text-xs text-gray-400">দোকান আপনার পেমেন্ট যাচাই করে নিশ্চিত করবে।</p>
    </form>
</div>
@endsection
