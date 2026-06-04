@extends('admin.layout')
@section('title', 'Vendor Wallet')

@section('content')
<h2 class="text-xl font-bold text-gray-800 mb-5">Vendor Wallet সমূহ</h2>

@if($vendors->isEmpty())
<div class="bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 text-sm">কোনো Vendor নেই।</div>
@else
<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vendor</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden sm:table-cell">মোট বিক্রয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Commission</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">আয়</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">পরিশোধিত</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">বাকি</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($vendors as $vendor)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-semibold text-gray-800">{{ $vendor->shop_name ?? $vendor->name }}</p>
                    <p class="text-gray-400 text-xs">{{ $vendor->email }}</p>
                </td>
                <td class="px-4 py-3 font-semibold text-gray-800 hidden sm:table-cell">—</td>
                <td class="px-4 py-3 text-indigo-600 font-semibold hidden md:table-cell">৳{{ number_format($vendor->total_commission_deducted ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-[#14532d] font-bold">৳{{ number_format($vendor->total_earning ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-green-600 font-semibold hidden lg:table-cell">৳{{ number_format($vendor->total_paid ?? 0, 2) }}</td>
                <td class="px-4 py-3 text-amber-600 font-bold">৳{{ number_format($vendor->pending_payout ?? 0, 2) }}</td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.vendor-wallet.show', $vendor->id) }}"
                       class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition-colors font-medium">
                        বিস্তারিত
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($vendors->hasPages())
    <div class="px-4 py-3 border-t">{{ $vendors->links() }}</div>
    @endif
</div>
@endif
@endsection
