@extends('admin.layout')
@section('title', 'ভেন্ডর স্টক')

@php
    $unitFmt = fn($v) => rtrim(rtrim(number_format((float)$v, 3, '.', ''), '0'), '.');
    $statusBadge = function($p) {
        return match($p->stockStatus()) {
            'out_of_stock' => ['স্টক শেষ', 'bg-red-100 text-red-700'],
            'low_stock'    => ['স্টক কম', 'bg-amber-100 text-amber-700'],
            default        => ['স্টক আছে', 'bg-green-100 text-green-700'],
        };
    };
@endphp

@section('content')
<div x-data="adminStockAdjust()">

    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <h2 class="text-lg font-bold text-gray-800">ভেন্ডর স্টক (অ্যাডমিন)</h2>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.vendor-stock.index') }}" class="flex flex-wrap gap-2 mb-4">
        <select name="vendor_id" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">সব ভেন্ডর</option>
            @foreach($vendors as $v)
                <option value="{{ $v->id }}" {{ (string)request('vendor_id') === (string)$v->id ? 'selected' : '' }}>{{ $v->shop_name }}</option>
            @endforeach
        </select>
        <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম / SKU খুঁজুন…"
               class="flex-1 min-w-[180px] border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
        <select name="status" class="border border-gray-300 rounded-lg px-3 py-2 text-sm bg-white">
            <option value="">সব স্ট্যাটাস</option>
            <option value="low" {{ request('status') === 'low' ? 'selected' : '' }}>স্টক কম</option>
            <option value="out" {{ request('status') === 'out' ? 'selected' : '' }}>স্টক শেষ</option>
        </select>
        <button class="bg-[#14532d] hover:bg-[#0d3520] text-white px-4 py-2 rounded-lg text-sm">খুঁজুন</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">পণ্য</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">ভেন্ডর</th>
                    <th class="text-left px-4 py-3 font-semibold text-gray-600">SKU</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">স্টক</th>
                    <th class="text-center px-4 py-3 font-semibold text-gray-600">স্ট্যাটাস</th>
                    <th class="text-right px-4 py-3 font-semibold text-gray-600">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($products as $p)
                    @php [$label, $cls] = $statusBadge($p); @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-800">{{ $p->name_bn ?: $p->name_en }}</div>
                            @if($p->category)<div class="text-xs text-gray-400">{{ $p->category }}</div>@endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $p->vendor?->shop_name ?? '—' }}</td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">{{ $p->sku ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-800">
                            {{ $unitFmt($p->onHand()) }} <span class="text-xs text-gray-400">{{ $p->stockUnit() }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $cls }}">{{ $label }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button type="button"
                                    @click="open({{ $p->id }}, @js($p->name_bn ?: $p->name_en), {{ (float)$p->onHand() }}, @js($p->stockUnit()))"
                                    class="text-indigo-600 hover:text-indigo-800 font-medium text-xs border border-indigo-200 rounded px-3 py-1">
                                স্টক আপডেট
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">কোনো ভেন্ডর পণ্য নেই।</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $products->links() }}</div>

    {{-- Adjust modal --}}
    <div x-show="show" x-cloak class="fixed inset-0 z-40 flex items-center justify-center p-4" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="show=false"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <h3 class="text-lg font-bold text-gray-800 mb-1">স্টক আপডেট (অ্যাডমিন)</h3>
            <p class="text-sm text-gray-500 mb-4" x-text="name + ' — বর্তমান: ' + current + ' ' + unit"></p>

            <form method="POST" action="{{ route('admin.vendor-stock.adjust') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="product_id" :value="productId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ধরন</label>
                    <select name="mode" x-model="mode" class="w-full border rounded-lg px-3 py-2 text-sm bg-white">
                        <option value="add">স্টক যোগ (+)</option>
                        <option value="reduce">স্টক কমানো (−)</option>
                        <option value="set">নির্দিষ্ট পরিমাণে সেট</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">পরিমাণ (<span x-text="unit"></span>)</label>
                    <input type="number" name="quantity" step="0.001" min="0" required x-ref="qty"
                           class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">নোট (ঐচ্ছিক)</label>
                    <input type="text" name="note" class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>

                <div class="flex justify-end gap-2 pt-1">
                    <button type="button" @click="show=false" class="px-4 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg">বাতিল</button>
                    <button type="submit" class="px-4 py-2 text-sm bg-[#14532d] hover:bg-[#0d3520] text-white rounded-lg font-medium">সংরক্ষণ</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>[x-cloak]{display:none!important;}</style>
<script>
function adminStockAdjust() {
    return {
        show: false, productId: null, name: '', current: 0, unit: '', mode: 'add',
        open(id, name, current, unit) {
            this.productId = id; this.name = name; this.current = current; this.unit = unit;
            this.mode = 'add'; this.show = true;
            this.$nextTick(() => this.$refs.qty && this.$refs.qty.focus());
        }
    };
}
</script>
@endsection
