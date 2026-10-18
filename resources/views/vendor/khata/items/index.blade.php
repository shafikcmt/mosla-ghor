@extends('vendor.khata.layout')
@section('title', 'ইনভেন্টরি')
@section('heading', 'ইনভেন্টরি')
@section('subheading', $items->count().'টি আইটেম · স্টক মূল্য ৳'.number_format($stockValue, 0))

@section('content')
@php $q3 = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.'); @endphp
<form method="GET" class="flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" placeholder="পণ্য অনুসন্ধান…" class="flex-1 min-w-0 border border-gray-200 rounded-xl px-3 py-2 text-sm bg-white">
    @if($categories->isNotEmpty())
    <select name="category" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-2 py-2 text-sm bg-white max-w-[38%]">
        <option value="">সব শ্রেণী</option>
        @foreach($categories as $c)<option value="{{ $c }}" @selected(request('category') === $c)>{{ $c }}</option>@endforeach
    </select>
    @endif
    <select name="stock" onchange="this.form.submit()" class="border border-gray-200 rounded-xl px-2 py-2 text-sm bg-white">
        <option value="">সব স্টক</option>
        <option value="low" @selected(request('stock') === 'low')>স্টক কম</option>
        <option value="out" @selected(request('stock') === 'out')>স্টক শেষ</option>
    </select>
</form>

@if($items->isNotEmpty())
<div class="hidden lg:block mt-4 rounded-2xl bg-white border border-gray-100 shadow-sm overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs">
            <tr>
                <th class="text-left font-semibold px-4 py-3">আইটেম</th>
                <th class="text-left font-semibold px-4 py-3">শ্রেণী</th>
                <th class="text-right font-semibold px-4 py-3">বিক্রয় মূল্য</th>
                <th class="text-right font-semibold px-4 py-3">ক্রয় মূল্য</th>
                <th class="text-right font-semibold px-4 py-3">মোট বিক্রি</th>
                <th class="text-right font-semibold px-4 py-3">স্টক</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @foreach($items as $item)
            @php $s = $sales[$item->id] ?? collect(); @endphp
            <tr onclick="location.href='{{ route('vendor.khata.items.show', $item) }}'" class="cursor-pointer hover:bg-green-50/50 {{ $item->is_active ? '' : 'opacity-60' }}">
                <td class="px-4 py-3">
                    <a href="{{ route('vendor.khata.items.show', $item) }}" class="flex items-center gap-3 font-semibold">
                        <span class="shrink-0 w-8 h-8 rounded-lg bg-green-50 text-[#0f7a3e] font-bold flex items-center justify-center">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span>
                        {{ $item->name }}
                    </a>
                </td>
                <td class="px-4 py-3 text-gray-500">{{ $item->category ?: '—' }}</td>
                <td class="px-4 py-3 text-right num">৳{{ number_format((float) $item->sale_price, 0) }}</td>
                <td class="px-4 py-3 text-right num text-gray-500">৳{{ number_format((float) $item->purchase_price, 0) }}</td>
                <td class="px-4 py-3 text-right num">৳{{ number_format((float) ($s->firstWhere('type', 'sale')->amount ?? 0), 0) }}</td>
                <td class="px-4 py-3 text-right font-bold num {{ (float) $item->stock <= 0 ? 'text-red-600' : ($item->isLow() ? 'text-amber-600' : 'text-gray-800') }}">{{ $item->stockLabel() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif

<div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2 {{ $items->isNotEmpty() ? 'lg:hidden' : '' }}">
    @forelse($items as $item)
    @php $s = $sales[$item->id] ?? collect(); @endphp
    <a href="{{ route('vendor.khata.items.show', $item) }}" class="rounded-2xl bg-white border border-gray-100 shadow-sm p-3 flex gap-3 hover:border-green-300 {{ $item->is_active ? '' : 'opacity-60' }}">
        <span class="shrink-0 w-10 h-10 rounded-xl bg-green-50 text-[#0f7a3e] font-bold flex items-center justify-center">{{ mb_strtoupper(mb_substr($item->name, 0, 1)) }}</span>
        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-2">
                <p class="font-bold truncate">{{ $item->name }}</p>
                @if($item->category)<span class="shrink-0 text-[11px] bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">{{ $item->category }}</span>@endif
            </div>
            <div class="grid grid-cols-3 gap-1 mt-1.5 text-xs">
                <div><p class="text-gray-400">বিক্রয়</p><p class="font-semibold num">৳{{ number_format((float) ($s->firstWhere('type', 'sale')->amount ?? 0), 0) }}</p></div>
                <div><p class="text-gray-400">ক্রয়</p><p class="font-semibold num">৳{{ number_format((float) ($s->firstWhere('type', 'purchase')->amount ?? 0), 0) }}</p></div>
                <div><p class="text-gray-400">স্টক</p>
                    <p class="font-bold num {{ (float) $item->stock <= 0 ? 'text-red-600' : ($item->isLow() ? 'text-amber-600' : 'text-gray-800') }}">{{ $item->stockLabel() }}</p></div>
            </div>
        </div>
    </a>
    @empty
    <div class="sm:col-span-2 rounded-2xl bg-white border border-dashed border-gray-300 p-10 text-center text-gray-500">
        <p class="text-4xl mb-2">📦</p>
        <p>এখনো কোনো আইটেম নেই।</p>
        <a href="{{ route('vendor.khata.items.create') }}" class="inline-block mt-3 bg-[#0f7a3e] text-white font-semibold px-5 py-2.5 rounded-xl">+ প্রথম আইটেম যোগ করুন</a>
    </div>
    @endforelse
</div>

<a href="{{ route('vendor.khata.items.create') }}" class="no-print fixed right-4 bottom-24 lg:right-8 lg:bottom-8 z-20 bg-[#0f7a3e] text-white font-semibold px-5 py-3 rounded-full shadow-lg">+ নতুন আইটেম</a>
@endsection
