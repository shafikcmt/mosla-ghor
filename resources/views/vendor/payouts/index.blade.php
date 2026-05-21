@extends('vendor.layout')
@section('title', 'পেআউট / উত্তোলন')

@section('content')

<h2 class="text-lg font-bold text-gray-800 mb-6">পেআউট / উত্তোলন</h2>

{{-- Balance summary --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">মোট আয়</p>
        <p class="text-xl font-bold text-gray-800 font-mono">৳{{ number_format($totalEarned, 0) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">মোট পেইড</p>
        <p class="text-xl font-bold text-green-600 font-mono">৳{{ number_format($totalPaid, 0) }}</p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <p class="text-xs text-gray-500 mb-1">পেন্ডিং রিকুয়েস্ট</p>
        <p class="text-xl font-bold text-yellow-600 font-mono">৳{{ number_format($pendingPayout, 0) }}</p>
    </div>
    <div class="bg-green-50 rounded-xl border border-green-200 p-4">
        <p class="text-xs text-green-700 mb-1 font-medium">উপলব্ধ ব্যালেন্স</p>
        <p class="text-xl font-bold text-green-700 font-mono">৳{{ number_format($available, 0) }}</p>
    </div>
</div>

{{-- Request form --}}
@if($vendor->isApproved() && $available > 0)
<div class="bg-white rounded-xl border border-gray-100 p-5 mb-6">
    <h3 class="font-semibold text-gray-700 text-sm mb-4">উত্তোলন রিকুয়েস্ট করুন</h3>
    <form method="POST" action="{{ route('vendor.payouts.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পরিমাণ (৳) <span class="text-red-500">*</span></label>
            <input type="number" name="amount" min="1" max="{{ $available }}" step="0.01"
                   value="{{ old('amount') }}" required
                   class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পেমেন্ট পদ্ধতি <span class="text-red-500">*</span></label>
            <select name="payment_method" required
                    class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">— বেছে নিন —</option>
                <option value="bkash" {{ old('payment_method') === 'bkash' ? 'selected' : '' }}>বিকাশ</option>
                <option value="nagad" {{ old('payment_method') === 'nagad' ? 'selected' : '' }}>নগদ</option>
                <option value="rocket" {{ old('payment_method') === 'rocket' ? 'selected' : '' }}>রকেট</option>
                <option value="bank" {{ old('payment_method') === 'bank' ? 'selected' : '' }}>ব্যাংক ট্রান্সফার</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">পেমেন্ট বিবরণ <span class="text-red-500">*</span></label>
            <input type="text" name="payment_details" value="{{ old('payment_details') }}"
                   placeholder="নম্বর / অ্যাকাউন্ট তথ্য" required
                   class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
        </div>
        <div class="md:col-span-3">
            <button type="submit"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-6 py-2 rounded-lg text-sm transition-colors">
                উত্তোলন রিকুয়েস্ট জমা দিন
            </button>
        </div>
    </form>
</div>
@endif

{{-- Payout history --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-700 text-sm">পেআউট ইতিহাস</h3>
    </div>
    @if($payouts->isEmpty())
        <div class="px-6 py-12 text-center text-gray-400 text-sm">এখনো কোনো পেআউট রিকুয়েস্ট নেই।</div>
    @else
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">তারিখ</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">পরিমাণ</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 hidden md:table-cell">পদ্ধতি</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600">অবস্থা</th>
                <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 hidden md:table-cell">নোট</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($payouts as $payout)
            <tr>
                <td class="px-4 py-3 text-gray-500 text-xs">{{ $payout->created_at->format('d M Y') }}</td>
                <td class="px-4 py-3 font-mono font-semibold">৳{{ number_format($payout->amount, 0) }}</td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-600">{{ $payout->payment_method }}</td>
                <td class="px-4 py-3">
                    <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium
                        @if($payout->status === 'paid') bg-green-100 text-green-700
                        @elseif($payout->status === 'approved') bg-blue-100 text-blue-700
                        @elseif($payout->status === 'pending') bg-yellow-100 text-yellow-700
                        @else bg-red-100 text-red-700 @endif">
                        @if($payout->status === 'paid') পরিশোধিত
                        @elseif($payout->status === 'approved') অনুমোদিত
                        @elseif($payout->status === 'pending') পেন্ডিং
                        @else প্রত্যাখ্যাত @endif
                    </span>
                </td>
                <td class="px-4 py-3 hidden md:table-cell text-gray-400 text-xs">{{ $payout->admin_note }}</td>
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
