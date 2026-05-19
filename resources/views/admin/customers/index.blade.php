@extends('admin.layout')

@section('title', 'কাস্টমার')

@section('content')

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
    <h1 class="text-xl font-bold text-gray-800">কাস্টমার তালিকা</h1>
    <div class="flex items-center gap-2">
        <span class="text-sm text-gray-500">মোট: {{ $customers->total() }} জন</span>
        <a href="{{ route('admin.customers.export') }}"
           class="inline-flex items-center gap-1.5 text-xs bg-green-700 hover:bg-green-800 text-white px-3 py-2 rounded transition-colors">
            ↓ CSV এক্সপোর্ট
        </a>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.customers.index') }}"
      class="bg-white rounded shadow-sm border border-gray-100 px-4 py-3 mb-4 flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
        <label class="block text-xs font-medium text-gray-600 mb-1">নাম / মোবাইল খুঁজুন</label>
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="নাম বা মোবাইল নম্বর"
               class="w-full border border-gray-200 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-700">
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">মার্কেটিং</label>
        <select name="marketing" class="border border-gray-200 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-700">
            <option value="">সব</option>
            <option value="1" {{ request('marketing') === '1' ? 'selected' : '' }}>সম্মত</option>
            <option value="0" {{ request('marketing') === '0' ? 'selected' : '' }}>অসম্মত</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">স্ট্যাটাস</label>
        <select name="status" class="border border-gray-200 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-green-700">
            <option value="">সব</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>সক্রিয়</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>নিষ্ক্রিয়</option>
        </select>
    </div>
    <div class="flex gap-2">
        <button type="submit"
                class="bg-gray-800 hover:bg-gray-700 text-white text-xs px-4 py-2 rounded transition-colors">
            ফিল্টার
        </button>
        @if(request()->hasAny(['search','marketing','status']))
        <a href="{{ route('admin.customers.index') }}"
           class="border border-gray-300 text-gray-600 hover:bg-gray-50 text-xs px-3 py-2 rounded transition-colors">
            রিসেট
        </a>
        @endif
    </div>
</form>

<div class="bg-white rounded shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">নাম</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">মোবাইল</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">অর্ডার</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">মোট খরচ (৳)</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">শেষ অর্ডার</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">মার্কেটিং</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">স্ট্যাটাস</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($customers as $customer)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 text-gray-800 font-medium">{{ $customer->name }}</td>
                <td class="px-4 py-3 text-gray-600 font-mono text-xs">{{ $customer->mobile_number }}</td>
                <td class="px-4 py-3 text-center text-gray-700">{{ $customer->total_orders }}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800">{{ number_format($customer->total_spent, 0) }}</td>
                <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                    {{ $customer->last_order_at?->format('d M Y') ?? '—' }}
                </td>
                <td class="px-4 py-3 text-center">
                    @if($customer->accepts_marketing)
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-700">✓ সম্মত</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-center">
                    @if($customer->is_active)
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-700">সক্রিয়</span>
                    @else
                        <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">নিষ্ক্রিয়</span>
                    @endif
                </td>
                <td class="px-4 py-3">
                    <a href="{{ route('admin.customers.show', $customer) }}"
                       class="inline-block bg-gray-800 text-white text-xs px-3 py-1 rounded hover:bg-gray-700 transition-colors">
                        দেখুন
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="px-4 py-8 text-center text-gray-400">কোনো কাস্টমার নেই।</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($customers->hasPages())
<div class="mt-5">
    {{ $customers->links() }}
</div>
@endif

@endsection
