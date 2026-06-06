@extends('admin.layout')
@section('title', 'ভেন্ডর কাস্টমার')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h2 class="text-lg font-bold text-gray-800">ভেন্ডর কাস্টমার (অ্যাডমিন)</h2>
</div>

<div class="grid grid-cols-2 gap-3 mb-5 max-w-md">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট কাস্টমার</div>
        <div class="text-2xl font-bold text-gray-800">{{ $summary['total'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট বাকি</div>
        <div class="text-2xl font-bold text-red-600">৳{{ number_format($summary['due_total'], 0) }}</div>
    </div>
</div>

<form method="GET" action="{{ route('admin.vendor-customers.index') }}" class="flex flex-wrap gap-2 mb-4">
    <select name="vendor_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব ভেন্ডর</option>
        @foreach($vendors as $v)
            <option value="{{ $v->id }}" {{ (string)request('vendor_id') === (string)$v->id ? 'selected' : '' }}>{{ $v->shop_name }}</option>
        @endforeach
    </select>
    <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম / ফোন খুঁজুন…"
           class="flex-1 min-w-[180px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব</option>
        <option value="due" {{ request('status') === 'due' ? 'selected' : '' }}>বাকি আছে</option>
    </select>
    <button class="bg-[#14532d] hover:bg-[#0d3520] text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">কাস্টমার</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">ভেন্ডর</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">ফোন</th>
                <th class="text-left px-4 py-3 font-semibold text-gray-600">ধরন</th>
                <th class="text-center px-4 py-3 font-semibold text-gray-600">অর্ডার</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">বাকি</th>
                <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($customers as $c)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800">{{ $c->name }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->vendor?->shop_name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $c->phone }}</td>
                    <td class="px-4 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">{{ $c->customer_type }}</span></td>
                    <td class="px-4 py-3 text-center text-gray-700">{{ $c->orders_count }}</td>
                    <td class="px-4 py-3 text-right font-semibold {{ $c->due_balance > 0 ? 'text-red-600' : 'text-gray-400' }}">৳{{ number_format($c->due_balance, 0) }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.vendor-customers.show', $c) }}" class="text-xs text-indigo-600 hover:underline">দেখুন</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">কোনো কাস্টমার নেই।</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">{{ $customers->links() }}</div>
@endsection
