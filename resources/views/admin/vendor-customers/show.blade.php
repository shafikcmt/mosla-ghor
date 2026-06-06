@extends('admin.layout')
@section('title', 'কাস্টমার — ' . $vendorCustomer->name)

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-lg font-bold text-gray-800">{{ $vendorCustomer->name }}</h2>
    <a href="{{ route('admin.vendor-customers.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← তালিকা</a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="bg-white rounded-xl border border-gray-100 p-5 text-sm space-y-1.5">
        <p class="text-sm font-bold text-gray-700 mb-2">তথ্য</p>
        <div class="flex justify-between"><span class="text-gray-500">ভেন্ডর</span><span>{{ $vendorCustomer->vendor?->shop_name ?? '—' }}</span></div>
        <div class="flex justify-between"><span class="text-gray-500">ফোন</span><span>{{ $vendorCustomer->phone }}</span></div>
        @if($vendorCustomer->whatsapp)<div class="flex justify-between"><span class="text-gray-500">WhatsApp</span><span>{{ $vendorCustomer->whatsapp }}</span></div>@endif
        <div class="flex justify-between"><span class="text-gray-500">ধরন</span><span>{{ $vendorCustomer->customer_type }}</span></div>
        <div class="flex justify-between items-center"><span class="text-gray-500">স্ট্যাটাস</span>
            <form method="POST" action="{{ route('admin.vendor-customers.toggle', $vendorCustomer) }}">
                @csrf
                <button class="text-xs px-2 py-0.5 rounded-full {{ $vendorCustomer->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} hover:opacity-80">
                    {{ $vendorCustomer->status === 'active' ? 'সক্রিয় (বন্ধ করুন)' : 'নিষ্ক্রিয় (চালু করুন)' }}
                </button>
            </form>
        </div>
        <div class="flex justify-between font-semibold"><span class="text-gray-500">বাকি</span><span class="{{ $vendorCustomer->due_balance > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($vendorCustomer->due_balance, 2) }}</span></div>
        @if($vendorCustomer->address)<div class="pt-2 border-t text-gray-600">{{ $vendorCustomer->address }}, {{ $vendorCustomer->area }} {{ $vendorCustomer->district }}</div>@endif
        @if($vendorCustomer->notes)<div class="pt-2 text-gray-600">নোট: {{ $vendorCustomer->notes }}</div>@endif
    </div>

    <div class="lg:col-span-2 bg-white rounded-xl border border-gray-100 overflow-hidden">
        <p class="text-sm font-bold text-gray-700 p-4 border-b">অর্ডার ইতিহাস ({{ $vendorCustomer->orders->count() }})</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                    <tr>
                        <th class="text-left px-4 py-3">অর্ডার নং</th>
                        <th class="text-left px-4 py-3">তারিখ</th>
                        <th class="text-right px-4 py-3">মোট</th>
                        <th class="text-right px-4 py-3">বাকি</th>
                        <th class="text-center px-4 py-3">পেমেন্ট</th>
                        <th class="text-right px-4 py-3">আদায়</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($vendorCustomer->orders as $o)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ $o->order_number }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $o->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">৳{{ number_format($o->grand_total, 0) }}</td>
                            <td class="px-4 py-3 text-right {{ $o->due_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($o->due_amount, 0) }}</td>
                            <td class="px-4 py-3 text-center text-xs">{{ $o->payment_status }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($o->due_amount > 0)
                                <form method="POST" action="{{ route('admin.vendor-orders.settle', $o) }}" class="flex items-center justify-end gap-1">
                                    @csrf
                                    <input type="number" name="amount" step="0.01" min="0.01" max="{{ $o->due_amount }}" value="{{ $o->due_amount }}"
                                           class="w-20 border rounded px-2 py-1 text-xs text-right">
                                    <button class="text-xs bg-[#14532d] text-white px-2 py-1 rounded hover:bg-[#0d3520]">আদায়</button>
                                </form>
                                @else
                                    <span class="text-xs text-gray-300">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-400">কোনো অর্ডার নেই।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
