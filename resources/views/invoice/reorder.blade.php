@extends('invoice.layout')
@section('title', 'আবার অর্ডার')

@section('content')
<div class="card bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h1 class="text-lg font-bold text-gray-800 mb-1">আবার অর্ডার করুন</h1>
    <p class="text-sm text-gray-500 mb-4">নিচের পণ্যগুলো দিয়ে একটি নতুন অর্ডার {{ $vendor->shop_name ?? '' }}-এর কাছে পাঠানো হবে। দোকান কনফার্ম করার পর দাম/স্টক যাচাই হবে।</p>

    <table class="w-full text-sm mb-4">
        <thead class="text-gray-400 text-xs uppercase border-b">
            <tr><th class="text-left py-2">পণ্য</th><th class="text-right py-2">পরিমাণ</th><th class="text-right py-2">আনুমানিক দাম</th></tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($order->items as $it)
                <tr>
                    <td class="py-2 text-gray-800">{{ $it->product_name }}</td>
                    <td class="py-2 text-right text-gray-600">{{ $it->quantityLabel() }}</td>
                    <td class="py-2 text-right text-gray-600">৳{{ number_format($it->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <form method="POST" action="{{ route('invoice.reorder.store', $order->invoice_token) }}" class="flex items-center gap-3">
        @csrf
        <button type="submit" class="bg-[#0f3d22] hover:bg-[#0a2c18] text-white px-5 py-2.5 rounded-lg text-sm font-bold">অর্ডার নিশ্চিত করুন</button>
        <a href="{{ route('invoice.show', $order->invoice_token) }}" class="text-sm text-gray-500 hover:text-gray-700">← ইনভয়েসে ফিরুন</a>
    </form>
</div>
@endsection
