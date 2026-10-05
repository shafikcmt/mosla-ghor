@extends('admin.layout')

@section('title', 'প্রাইস বোর্ড')

@section('content')
@php
    $field = 'w-28 border border-gray-300 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:ring-1 focus:ring-green-500';
@endphp

<div class="flex flex-wrap justify-between items-start gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-gray-800">প্রাইস বোর্ড — খুচরা ও পাইকারি</h1>
        <p class="text-sm text-gray-500 mt-1">এক পেজে সব পণ্যের প্রতি কেজির দাম বসান। খুচরা দাম বদলালে প্যাকের দাম নিজে থেকে হিসাব হয় (ম্যানুয়াল প্যাক দাম অপরিবর্তিত থাকে)। Facebook বট এই দামই পড়ে উত্তর দেয় — <a href="{{ route('admin.bot-api.index') }}" class="text-blue-600 underline">Bot API</a>।</p>
    </div>
</div>

<form method="GET" action="{{ route('admin.price-board.index') }}" class="bg-white rounded shadow px-4 py-3 mb-4 flex flex-col sm:flex-row gap-2" role="search">
    <input type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="নাম, স্লাগ বা SKU দিয়ে খুঁজুন…" aria-label="পণ্য খুঁজুন"
           class="flex-1 min-w-0 border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-green-500">
    <select name="channel" class="border border-gray-300 rounded px-3 py-2 text-sm bg-white" aria-label="বিক্রয় মাধ্যম" onchange="this.form.requestSubmit()">
        <option value="">সব পণ্য</option>
        <option value="retail" @selected($channel === 'retail')>খুচরা</option>
        <option value="wholesale" @selected($channel === 'wholesale')>পাইকারি</option>
    </select>
    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">খুঁজুন</button>
</form>

@if($products->isEmpty())
    <div class="bg-white rounded shadow p-8 text-center text-gray-500">কোনো পণ্য পাওয়া যায়নি।</div>
@else
<form method="POST" action="{{ route('admin.price-board.update') }}">
    @csrf
    <div class="bg-white rounded shadow overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-left">
                <tr>
                    <th class="px-4 py-3">পণ্য</th>
                    <th class="px-4 py-3">খুচরা (৳ / কেজি)</th>
                    <th class="px-4 py-3">খুচরা প্যাক</th>
                    <th class="px-4 py-3">পাইকারি (৳ / কেজি)</th>
                    <th class="px-4 py-3">পাইকারি একক / MOQ</th>
                    <th class="px-4 py-3">বটের উত্তর</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
            @foreach($products as $product)
                <tr class="align-top {{ $product->is_active ? '' : 'bg-gray-50 text-gray-500' }}">
                    <td class="px-4 py-3 min-w-[180px]">
                        <a href="{{ route('admin.products.edit', $product) }}" class="font-medium text-gray-800 hover:text-green-700">{{ $product->display_name }}</a>
                        <div class="flex flex-wrap gap-1 mt-1 text-[10px]">
                            @unless($product->is_active)<span class="px-1.5 py-0.5 rounded-full bg-gray-200">নিষ্ক্রিয়</span>@endunless
                            @if($product->show_in_retail)<span class="px-1.5 py-0.5 rounded-full bg-green-50 text-green-700 border border-green-200">খুচরা</span>@endif
                            @if($product->show_in_wholesale)<span class="px-1.5 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200">পাইকারি</span>@endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        @if($product->show_in_retail && ! $product->variants_count)
                            <input type="number" name="rows[{{ $product->id }}][retail_price_1kg]" value="{{ old("rows.{$product->id}.retail_price_1kg", (float) $product->retail_price_1kg ?: '') }}"
                                   min="0.01" step="0.01" max="99999999.99" class="{{ $field }}" aria-label="{{ $product->display_name }} খুচরা দাম">
                            @error("rows.{$product->id}.retail_price_1kg")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @elseif($product->variants_count)
                            <span class="text-xs text-gray-500">ভ্যারিয়েন্ট — <a class="text-blue-600 underline" href="{{ route('admin.products.edit', $product) }}">সম্পাদনায়</a></span>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 min-w-[140px]">
                        @forelse($product->activeRetailPrices->take(6) as $pack)
                            <div>{{ $pack->weight_label }}@if($pack->variant) ({{ $pack->variant->name }})@endif: <strong>{{ \App\Support\PriceReply::money((float) $pack->final_price) }}</strong></div>
                        @empty
                            <span class="text-gray-400">—</span>
                        @endforelse
                    </td>
                    <td class="px-4 py-3">
                        @if($product->show_in_wholesale)
                            <input type="number" name="rows[{{ $product->id }}][wholesale_price_1kg]" value="{{ old("rows.{$product->id}.wholesale_price_1kg", $product->wholesale_price_1kg !== null ? (float) $product->wholesale_price_1kg : '') }}"
                                   min="0" step="0.01" max="99999999.99" placeholder="কোটেশন" class="{{ $field }}" aria-label="{{ $product->display_name }} পাইকারি দাম">
                            @error("rows.{$product->id}.wholesale_price_1kg")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-xs text-gray-600 min-w-[160px]">
                        @if($product->show_in_wholesale)
                            @foreach($product->wholesaleUnitPrices() as $u)
                                <div>প্রতি {{ $u['unit_label'] }} ({{ \App\Models\Product::formatQty($u['kg']) }} কেজি): <strong>{{ \App\Support\PriceReply::money($u['price']) }}</strong></div>
                            @endforeach
                            @foreach($product->unitConversionLabels() as $label)<div class="text-gray-400">{{ $label }}</div>@endforeach
                            @if($product->moqLabel())<div>MOQ: {{ \App\Models\Product::formatQty((float) $product->min_order_quantity) }} {{ \App\Models\Product::unitLabel($product->min_order_unit ?: 'kg') }}</div>@endif
                            @if(! $product->unitConversionRows())<a href="{{ route('admin.products.edit', $product) }}#pe-wholesale" class="text-blue-600 underline">ইউনিট কনভার্শন যোগ করুন</a>@endif
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 min-w-[120px]">
                        <details>
                            <summary class="cursor-pointer text-xs text-blue-600">উত্তর দেখুন</summary>
                            @foreach(\App\Support\PriceReply::TYPES as $type => $label)
                                <p class="text-[11px] font-semibold text-gray-500 mt-2">{{ $label }} কাস্টমার</p>
                                <textarea readonly rows="5" onclick="this.select()" class="w-64 text-xs border border-gray-200 rounded p-2 bg-gray-50">{{ \App\Support\PriceReply::text($product, $type) }}</textarea>
                            @endforeach
                        </details>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
        <div>{{ $products->links() }}</div>
        <button type="submit" class="bg-green-600 text-white px-6 py-2.5 rounded text-sm font-semibold hover:bg-green-700">এই পেজের দাম সংরক্ষণ করুন</button>
    </div>
</form>
@endif
@endsection
