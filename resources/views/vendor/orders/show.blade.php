@extends('vendor.layout')
@section('title', 'অর্ডার বিস্তারিত')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.orders.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← অর্ডার তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $vendorOrder->order?->order_number }}</h1>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">অর্ডার তথ্য</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">তারিখ</dt>
                <dd class="font-medium">{{ $vendorOrder->created_at->format('d M Y, h:i A') }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">সাবটোটাল</dt>
                <dd class="font-mono font-semibold">৳{{ number_format($vendorOrder->subtotal, 2) }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">কমিশন</dt>
                <dd class="font-mono text-red-600">- ৳{{ number_format($vendorOrder->commission_amount, 2) }}</dd>
            </div>
            <div class="flex justify-between border-t pt-2">
                <dt class="text-gray-700 font-semibold">প্রাপ্য পরিমাণ</dt>
                <dd class="font-mono font-bold text-green-700">৳{{ number_format($vendorOrder->payable_amount, 2) }}</dd>
            </div>
        </dl>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 p-5">
        <h3 class="font-semibold text-gray-700 text-sm mb-3">কাস্টমার তথ্য</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-gray-500">নাম</dt>
                <dd class="font-medium">{{ $vendorOrder->order?->customer_name }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">মোবাইল</dt>
                <dd class="font-mono">{{ $vendorOrder->order?->mobile_number }}</dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-gray-500">অর্ডার অবস্থা</dt>
                <dd class="font-medium">{{ $vendorOrder->order?->order_status }}</dd>
            </div>
        </dl>
    </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">আমার পণ্যসমূহ (এই অর্ডারে)</h3>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পণ্য</th>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600">পরিমাণ</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600">মূল্য</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($vendorOrder->order->items as $item)
            <tr>
                <td class="px-4 py-3">
                    <p class="font-medium text-gray-800">{{ $item->product_name }}</p>
                    @if($item->variant_name)<p class="text-xs text-gray-400">{{ $item->variant_name }}</p>@endif
                    <span class="text-xs px-1.5 py-0.5 rounded {{ $item->sell_type === 'retail' ? 'bg-blue-100 text-blue-700' : 'bg-orange-100 text-orange-700' }}">
                        {{ $item->sell_type === 'retail' ? 'খুচরা' : 'পাইকারি' }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-600">
                    {{ $item->quantity_gram >= 1000
                        ? ($item->quantity_gram / 1000) . ' কেজি'
                        : $item->quantity_gram . ' গ্রাম' }}
                </td>
                <td class="px-4 py-3 text-right font-mono font-semibold">৳{{ number_format($item->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

@endsection
