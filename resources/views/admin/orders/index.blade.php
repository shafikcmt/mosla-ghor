@extends('admin.layout')

@section('title', 'অর্ডার তালিকা')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h1 class="text-xl font-bold text-gray-800">অর্ডার তালিকা</h1>
    <span class="text-sm text-gray-500">মোট: {{ $orders->total() }} টি অর্ডার</span>
</div>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার নং</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">নাম</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">মোবাইল</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">ধরন</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">মোট (৳)</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">পেমেন্ট</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">পেমেন্ট স্ট্যাটাস</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">অর্ডার স্ট্যাটাস</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">তারিখ</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($orders as $order)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $order->order_number }}</td>
                <td class="px-4 py-3 text-gray-800">{{ $order->customer_name }}</td>
                <td class="px-4 py-3 text-gray-600">{{ $order->mobile_number }}</td>
                <td class="px-4 py-3">
                    @php
                        $typeLabels = ['custom' => 'কাস্টম', 'retail' => 'রিটেইল', 'wholesale' => 'হোলসেল'];
                        $typeColors = ['custom' => 'bg-purple-100 text-purple-700', 'retail' => 'bg-blue-100 text-blue-700', 'wholesale' => 'bg-orange-100 text-orange-700'];
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $typeColors[$order->order_type] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $typeLabels[$order->order_type] ?? $order->order_type }}
                    </span>
                </td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($order->grand_total, 0) }}</td>
                <td class="px-4 py-3 text-gray-600 text-xs">
                    @php
                        $methodLabels = ['cash_on_delivery' => 'ক্যাশ অন ডেলিভারি', 'bkash' => 'বিকাশ', 'nagad' => 'নগদ', 'rocket' => 'রকেট'];
                    @endphp
                    {{ $methodLabels[$order->payment_method] ?? $order->payment_method }}
                </td>
                <td class="px-4 py-3">
                    @php
                        $pColors = ['pending' => 'bg-yellow-100 text-yellow-700', 'verified' => 'bg-green-100 text-green-700', 'failed' => 'bg-red-100 text-red-700'];
                        $pLabels = ['pending' => 'অপেক্ষায়', 'verified' => 'যাচাই হয়েছে', 'failed' => 'ব্যর্থ'];
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $pColors[$order->payment_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $pLabels[$order->payment_status] ?? $order->payment_status }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @php
                        $oColors = [
                            'pending'    => 'bg-yellow-100 text-yellow-700',
                            'confirmed'  => 'bg-blue-100 text-blue-700',
                            'processing' => 'bg-indigo-100 text-indigo-700',
                            'shipped'    => 'bg-cyan-100 text-cyan-700',
                            'delivered'  => 'bg-green-100 text-green-700',
                            'cancelled'  => 'bg-red-100 text-red-700',
                        ];
                        $oLabels = [
                            'pending'    => 'অপেক্ষায়',
                            'confirmed'  => 'নিশ্চিত',
                            'processing' => 'প্রসেসিং',
                            'shipped'    => 'শিপড',
                            'delivered'  => 'ডেলিভারড',
                            'cancelled'  => 'বাতিল',
                        ];
                    @endphp
                    <span class="px-2 py-0.5 rounded text-xs font-medium {{ $oColors[$order->order_status] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $oLabels[$order->order_status] ?? $order->order_status }}
                    </span>
                </td>
                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                    {{ $order->created_at->format('d M Y, h:i A') }}
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.orders.show', $order) }}"
                       class="inline-block bg-gray-800 text-white text-xs px-3 py-1 rounded hover:bg-gray-700 transition-colors">
                        দেখুন
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="10" class="px-4 py-8 text-center text-gray-400">কোনো অর্ডার নেই।</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($orders->hasPages())
<div class="mt-5">
    {{ $orders->links() }}
</div>
@endif

@endsection
