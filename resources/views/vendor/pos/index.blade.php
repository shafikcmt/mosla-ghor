@extends('vendor.layout')
@section('title', 'বিক্রয় (POS)')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-800">বিক্রয় তালিকা</h1>
    <a href="{{ route('vendor.pos.create') }}"
       class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ নতুন বিক্রয়</a>
</div>

<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট বিক্রয়</div>
        <div class="text-2xl font-bold text-gray-800">{{ $summary['count'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট আয়</div>
        <div class="text-2xl font-bold text-green-600">৳{{ number_format($summary['sales'], 0) }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট বাকি</div>
        <div class="text-2xl font-bold text-red-600">৳{{ number_format($summary['due_total'], 0) }}</div>
    </div>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="অর্ডার নং / নাম / ফোন…"
           class="flex-1 min-w-[200px] border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    <select name="payment" class="border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব</option>
        <option value="paid" {{ request('payment') === 'paid' ? 'selected' : '' }}>পরিশোধিত</option>
        <option value="due"  {{ request('payment') === 'due' ? 'selected' : '' }}>বাকি আছে</option>
    </select>
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">অর্ডার নং</th>
                    <th class="text-left px-4 py-3">কাস্টমার</th>
                    <th class="text-left px-4 py-3">তারিখ</th>
                    <th class="text-right px-4 py-3">মোট</th>
                    <th class="text-right px-4 py-3">বাকি</th>
                    <th class="text-center px-4 py-3">পেমেন্ট</th>
                    <th class="text-right px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($orders as $o)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-mono text-xs text-gray-700">{{ $o->order_number }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $o->customer_name ?: ($o->vendorCustomer?->name ?? '—') }}<br>
                            <span class="text-xs text-gray-400">{{ $o->mobile_number }}</span></td>
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $o->created_at->format('d M, H:i') }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">৳{{ number_format($o->grand_total, 0) }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $o->due_amount > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($o->due_amount, 0) }}</td>
                        <td class="px-4 py-3 text-center">
                            @php $pc = ['paid' => ['পরিশোধিত','bg-green-100 text-green-700'], 'partial' => ['আংশিক','bg-amber-100 text-amber-700']][$o->payment_status] ?? ['বাকি','bg-red-100 text-red-700']; @endphp
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $pc[1] }}">{{ $pc[0] }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('vendor.pos.show', $o) }}" class="text-xs text-indigo-600 hover:underline">দেখুন</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">কোনো বিক্রয় নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
