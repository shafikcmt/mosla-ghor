@extends('admin.layout')
@section('title', 'Commission Ledger')

@section('content')
<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">Commission Ledger</h2>
    <a href="{{ route('admin.commission.settings.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-semibold">← Commission সেটিং</a>
</div>

@if(session('success'))
<div class="mb-4 bg-green-50 border border-green-200 rounded-xl p-4 text-sm text-green-700">{{ session('success') }}</div>
@endif

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">মোট বিক্রয়</p>
        <p class="text-2xl font-bold text-gray-800">৳{{ number_format($totals['total_sales'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">মোট Commission</p>
        <p class="text-2xl font-bold text-indigo-600">৳{{ number_format($totals['total_commission'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">পরিশোধিত</p>
        <p class="text-2xl font-bold text-green-600">৳{{ number_format($totals['settled'] ?? 0, 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">অপেক্ষায়</p>
        <p class="text-2xl font-bold text-amber-600">৳{{ number_format($totals['pending'] ?? 0, 2) }}</p>
    </div>
</div>

{{-- Ledger table --}}
@if($ledger->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 text-sm">কোনো লেজার এন্ট্রি নেই।</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">#</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">ধরন</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বিক্রয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Commission</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor আয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($ledger as $entry)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-400 text-xs">#{{ $entry->id }}</td>
                <td class="px-4 py-3 font-medium text-gray-800 text-xs">{{ $entry->vendor?->shop_name ?? $entry->vendor?->name }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs hidden sm:table-cell capitalize">{{ $entry->order_type }}</td>
                <td class="px-4 py-3 font-semibold text-gray-800">৳{{ number_format($entry->subtotal, 2) }}</td>
                <td class="px-4 py-3 text-indigo-600 font-semibold">৳{{ number_format($entry->commission_amount, 2) }}</td>
                <td class="px-4 py-3 text-[#14532d] font-bold">৳{{ number_format($entry->vendor_earning, 2) }}</td>
                <td class="px-4 py-3">
                    <span class="text-xs px-2 py-0.5 rounded-full font-medium
                        {{ $entry->settlement_status === 'settled' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        {{ $entry->settlement_status === 'settled' ? 'পরিশোধিত' : 'অপেক্ষায়' }}
                    </span>
                </td>
                <td class="px-4 py-3">
                    @if($entry->settlement_status !== 'settled')
                    <form action="{{ route('admin.commission.ledger.settle', $entry->id) }}" method="POST">
                        @csrf
                        <button type="submit" onclick="return confirm('Settle করবেন?')"
                                class="text-xs bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-lg font-medium">
                            Settle
                        </button>
                    </form>
                    @else
                    <span class="text-xs text-gray-400">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($ledger->hasPages())
    <div class="px-4 py-3 border-t">{{ $ledger->links() }}</div>
    @endif
</div>
@endif
@endsection
