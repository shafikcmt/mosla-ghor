@extends('vendor.layout')
@section('title', 'কাস্টমার')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-800">আমার কাস্টমার</h1>
    <a href="{{ route('vendor.customers.create') }}"
       class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">+ নতুন কাস্টমার</a>
</div>

{{-- Summary --}}
<div class="grid grid-cols-3 gap-3 mb-5">
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট কাস্টমার</div>
        <div class="text-2xl font-bold text-gray-800">{{ $summary['total'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">বাকি আছে যাদের</div>
        <div class="text-2xl font-bold text-amber-600">{{ $summary['due_count'] }}</div>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 p-4">
        <div class="text-xs text-gray-400">মোট বাকি</div>
        <div class="text-2xl font-bold text-red-600">৳{{ number_format($summary['due_total'], 0) }}</div>
    </div>
</div>

{{-- Filters --}}
<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম / ফোন খুঁজুন…"
           class="flex-1 min-w-[200px] border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
    <select name="type" class="border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব ধরন</option>
        @foreach(\App\Models\VendorCustomer::CUSTOMER_TYPES as $t)
            <option value="{{ $t }}" {{ request('type') === $t ? 'selected' : '' }}>{{ $t }}</option>
        @endforeach
    </select>
    <select name="status" class="border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব</option>
        <option value="due" {{ request('status') === 'due' ? 'selected' : '' }}>বাকি আছে</option>
    </select>
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">নাম</th>
                    <th class="text-left px-4 py-3">ফোন</th>
                    <th class="text-left px-4 py-3">ধরন</th>
                    <th class="text-center px-4 py-3">অর্ডার</th>
                    <th class="text-right px-4 py-3">বাকি</th>
                    <th class="text-center px-4 py-3">স্ট্যাটাস</th>
                    <th class="text-right px-4 py-3">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($customers as $c)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $c->name }}</div>
                            @if($c->area || $c->district)<div class="text-xs text-gray-400">{{ $c->area }}{{ $c->area && $c->district ? ', ' : '' }}{{ $c->district }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $c->phone }}</td>
                        <td class="px-4 py-3"><span class="inline-block px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-600">{{ $c->customer_type }}</span></td>
                        <td class="px-4 py-3 text-center text-gray-700">{{ $c->orders_count }}</td>
                        <td class="px-4 py-3 text-right font-semibold {{ $c->due_balance > 0 ? 'text-red-600' : 'text-gray-400' }}">
                            ৳{{ number_format($c->due_balance, 0) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $c->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ $c->status === 'active' ? 'সক্রিয়' : 'নিষ্ক্রিয়' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('vendor.pos.create', ['customer' => $c->id]) }}" class="text-xs text-green-600 hover:underline">বিক্রয়</a>
                                <a href="{{ route('vendor.customers.edit', $c) }}" class="text-xs text-indigo-600 hover:underline">সম্পাদনা</a>
                                <form method="POST" action="{{ route('vendor.customers.destroy', $c) }}" class="inline"
                                      onsubmit="return confirm('এই কাস্টমার মুছে ফেলবেন?')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs text-red-500 hover:underline">মুছুন</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-400">কোনো কাস্টমার নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $customers->links() }}</div>
@endsection
