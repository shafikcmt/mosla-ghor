@extends('vendor.layout')
@section('title', 'স্টক হিস্ট্রি')

@php
    $unitFmt = fn($v) => rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.');
@endphp

@section('content')

<div class="flex flex-wrap items-center justify-between gap-3 mb-5">
    <h1 class="text-xl font-bold text-gray-800">স্টক হিস্ট্রি</h1>
    <a href="{{ route('vendor.stock.index') }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">← স্টক তালিকা</a>
</div>

<form method="GET" class="flex flex-wrap gap-2 mb-4">
    <select name="type" class="border rounded-lg px-3 py-2 text-sm bg-white">
        <option value="">সব ধরন</option>
        @foreach(\App\Models\VendorStockMovement::TYPES as $k => $v)
            <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ $v }}</option>
        @endforeach
    </select>
    <button class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">ফিল্টার</button>
</form>

<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                <tr>
                    <th class="text-left px-4 py-3">তারিখ</th>
                    <th class="text-left px-4 py-3">পণ্য</th>
                    <th class="text-left px-4 py-3">ধরন</th>
                    <th class="text-right px-4 py-3">পরিবর্তন</th>
                    <th class="text-right px-4 py-3">আগে → পরে</th>
                    <th class="text-left px-4 py-3">নোট</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($movements as $m)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap">{{ $m->created_at->format('d M, H:i') }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $m->product?->name_bn ?: ($m->product?->name_en ?? '—') }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-block px-2 py-0.5 rounded text-xs font-medium
                                {{ in_array($m->type, ['add','return']) ? 'bg-green-100 text-green-700' : (in_array($m->type, ['reduce','order','cancel']) ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                                {{ $m->typeLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold {{ $m->quantity >= 0 ? 'text-green-600' : 'text-red-600' }}">
                            {{ $m->quantity >= 0 ? '+' : '' }}{{ $unitFmt($m->quantity) }}
                        </td>
                        <td class="px-4 py-3 text-right text-gray-500 font-mono text-xs">{{ $unitFmt($m->previous_stock) }} → {{ $unitFmt($m->new_stock) }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $m->note ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">কোনো স্টক মুভমেন্ট নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $movements->links() }}</div>

@endsection
