@extends('admin.layout')
@section('title', 'ভেন্ডর পেআউট')

@section('content')

<div class="flex items-center justify-between mb-5">
    <h2 class="text-xl font-bold text-gray-800">ভেন্ডর পেআউট রিকুয়েস্ট</h2>
    <form method="GET" class="flex gap-2">
        <select name="status" onchange="this.form.submit()"
                class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
            <option value="">— সব অবস্থা —</option>
            @foreach(['pending','approved','paid','rejected'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                @if($s==='pending') পেন্ডিং
                @elseif($s==='approved') অনুমোদিত
                @elseif($s==='paid') পরিশোধিত
                @else প্রত্যাখ্যাত @endif
            </option>
            @endforeach
        </select>
    </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    @if($payouts->isEmpty())
        <div class="px-6 py-16 text-center text-gray-400 text-sm">কোনো পেআউট রিকুয়েস্ট নেই।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">ভেন্ডর</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">পরিমাণ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">পদ্ধতি</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase hidden md:table-cell">বিবরণ</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600 text-xs uppercase">অবস্থা</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600 text-xs uppercase">কার্যক্রম</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($payouts as $payout)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">
                    <p class="font-medium">{{ $payout->vendor?->shop_name }}</p>
                    <p class="text-xs text-gray-400">{{ $payout->created_at->format('d M Y') }}</p>
                </td>
                <td class="px-4 py-3 font-mono font-semibold">৳{{ number_format($payout->amount, 0) }}</td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ $payout->payment_method }}</td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-500 text-xs max-w-40 truncate">{{ $payout->payment_details }}</td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        @if($payout->status === 'paid') bg-green-100 text-green-700
                        @elseif($payout->status === 'approved') bg-blue-100 text-blue-700
                        @elseif($payout->status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-red-100 text-red-700 @endif">
                        @if($payout->status==='paid') পরিশোধিত
                        @elseif($payout->status==='approved') অনুমোদিত
                        @elseif($payout->status==='pending') পেন্ডিং
                        @else প্রত্যাখ্যাত @endif
                    </span>
                </td>
                <td class="px-4 py-3 text-right">
                    @if($payout->status === 'pending')
                    <form method="POST" action="{{ route('admin.vendor-payouts.approve', $payout) }}" class="inline">
                        @csrf
                        <button class="text-xs text-blue-700 border border-blue-200 rounded px-2 py-0.5 hover:bg-blue-50 mr-1">অনুমোদন</button>
                    </form>
                    <form method="POST" action="{{ route('admin.vendor-payouts.reject', $payout) }}" class="inline">
                        @csrf
                        <button class="text-xs text-red-600 border border-red-200 rounded px-2 py-0.5 hover:bg-red-50">প্রত্যাখ্যান</button>
                    </form>
                    @elseif($payout->status === 'approved')
                    <form method="POST" action="{{ route('admin.vendor-payouts.mark-paid', $payout) }}" class="inline">
                        @csrf
                        <button class="text-xs text-green-700 border border-green-200 rounded px-2 py-0.5 hover:bg-green-50">পরিশোধ চিহ্নিত</button>
                    </form>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($payouts->hasPages())
    <div class="px-4 py-3 border-t border-gray-100">{{ $payouts->links() }}</div>
    @endif
    @endif
</div>
@endsection
