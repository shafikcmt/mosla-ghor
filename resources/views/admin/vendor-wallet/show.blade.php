@extends('admin.layout')
@section('title', 'Vendor Wallet বিস্তারিত')

@section('content')
<div class="mb-5 flex items-center gap-3">
    <a href="{{ route('admin.vendor-wallet.index') }}" class="text-gray-500 hover:text-gray-700 text-sm">← Vendor Wallet</a>
</div>

<div class="flex items-center gap-4 mb-6">
    <div>
        <h2 class="text-xl font-bold text-gray-800">{{ $vendor->shop_name ?? $vendor->name }}</h2>
        <p class="text-gray-400 text-sm">{{ $vendor->email }}</p>
    </div>
</div>

{{-- Summary --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">মোট বিক্রয়</p>
        <p class="text-2xl font-bold text-gray-800">৳{{ number_format($summary['total_sales'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Commission কর্তন</p>
        <p class="text-2xl font-bold text-indigo-600">৳{{ number_format($summary['total_commission'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">মোট আয়</p>
        <p class="text-2xl font-bold text-[#14532d]">৳{{ number_format($summary['total_earning'], 2) }}</p>
    </div>
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">পরিশোধিত</p>
        <p class="text-2xl font-bold text-green-600">৳{{ number_format($summary['settled_amount'], 2) }}</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <div class="lg:col-span-2 space-y-5">

        {{-- Wholesale ledger --}}
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b">
                <h3 class="font-bold text-gray-800 text-sm">Wholesale Commission Ledger</h3>
            </div>
            @if($ledger->isEmpty())
            <div class="p-8 text-center text-gray-400 text-sm">কোনো এন্ট্রি নেই।</div>
            @else
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">অর্ডার</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বিক্রয়</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Commission</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">আয়</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($ledger as $entry)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-400 text-xs">
                            @if($entry->order_id)#{{ $entry->order_id }}@else —@endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-800">৳{{ number_format($entry->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-indigo-600 font-semibold">৳{{ number_format($entry->commission_amount, 2) }}</td>
                        <td class="px-4 py-3 text-[#14532d] font-bold">৳{{ number_format($entry->vendor_earning, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium
                                {{ $entry->settlement_status === 'settled' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $entry->settlement_status === 'settled' ? 'পরিশোধিত' : 'অপেক্ষায়' }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @if($ledger->hasPages())
            <div class="px-4 py-3 border-t">{{ $ledger->links() }}</div>
            @endif
            @endif
        </div>
    </div>

    {{-- Sidebar: pending payout + settle all --}}
    <div class="space-y-4">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Payout বাকি</p>
            <p class="text-3xl font-bold text-amber-600 mb-4">৳{{ number_format($summary['pending_payout'] ?? $summary['pending_amount'], 2) }}</p>

            @if(($summary['pending_payout'] ?? $summary['pending_amount']) > 0)
            <form action="{{ route('admin.commission.ledger.bulk-settle') }}" method="POST">
                @csrf
                <input type="hidden" name="vendor_id" value="{{ $vendor->id }}">
                <button type="submit" onclick="return confirm('সকল pending entry settle করবেন?')"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 rounded-xl text-sm transition-colors">
                    সব Settle করুন
                </button>
            </form>
            @else
            <div class="text-sm text-green-700 bg-green-50 border border-green-200 rounded-xl p-3 text-center">
                সব পরিশোধিত ✓
            </div>
            @endif
        </div>

        <div class="bg-indigo-50 border border-indigo-200 rounded-2xl p-4 text-xs text-indigo-800 leading-relaxed">
            Commission Admin কর্তৃক নির্ধারিত হয়। Settlement-এর সময় vendor earning স্বয়ংক্রিয়ভাবে সমন্বয় হয়।
        </div>
    </div>
</div>
@endsection
