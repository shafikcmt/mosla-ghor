@extends('vendor.layout')
@section('title', $product->name_bn . ' — সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('vendor.products.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name_bn }}</h1>
    @if(! $product->is_active)
        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
    @endif
    @if($product->approval_status === 'pending')
        <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded-full">অনুমোদন অপেক্ষায়</span>
    @endif
</div>

<form action="{{ route('vendor.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">পণ্যের তথ্য</h2>
        @include('vendor.products._form', ['product' => $product])
    </div>

    {{-- Retail price overrides --}}
    @if($retailPrices->isNotEmpty())
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">খুচরা প্যাক সাইজ ও দাম</h2>
        <p class="text-xs text-gray-400 mb-4">ম্যানুয়াল দাম চালু করলে সেটি গ্রাহকের কাছে দেখানো হবে।</p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">প্যাক</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">স্বয়ংক্রিয় দাম</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">ম্যানুয়াল দাম (৳)</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">ম্যানুয়াল?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">চূড়ান্ত দাম</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($retailPrices as $price)
                    <tr class="{{ $price->is_manual_override ? 'bg-amber-50' : '' }} {{ ! $price->is_active ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2.5 font-medium">{{ $price->label }} <span class="text-xs text-gray-400">({{ $price->quantity_gram }}g)</span></td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono">৳{{ $price->auto_price }}</td>
                        <td class="px-3 py-2.5">
                            <input type="number" name="prices[{{ $price->id }}][manual_price]"
                                   value="{{ $price->manual_price }}" step="0.01" min="0" placeholder="—"
                                   class="border rounded px-2 py-1 w-24 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" name="prices[{{ $price->id }}][is_manual_override]" value="1"
                                   {{ $price->is_manual_override ? 'checked' : '' }}>
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox" name="prices[{{ $price->id }}][is_active]" value="1"
                                   {{ $price->is_active ? 'checked' : '' }}>
                        </td>
                        <td class="px-3 py-2.5 font-mono font-semibold text-green-700">৳{{ $price->final_price }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Wholesale prices --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">পাইকারি বিক্রয় অপশন</h2>
        <p class="text-xs text-gray-400 mb-4">পাইকারি ট্যাবে দেখানো হবে। লেবেল উদাহরণ: ২৫ কেজি বস্তা, ৫০ কেজি বস্তা, ১২ পিস কার্টন।</p>

        @if($wholesalePrices->isNotEmpty())
        <div class="overflow-x-auto mb-4">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">লেবেল</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">গ্রাম</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">দাম (৳)</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">সর্বনিম্ন অর্ডার</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">মুছুন?</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($wholesalePrices as $price)
                    <tr>
                        <td class="px-3 py-2">
                            <input type="text" name="wholesale_prices[{{ $price->id }}][label]"
                                   value="{{ $price->label }}"
                                   class="border rounded px-2 py-1 w-40 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </td>
                        <td class="px-3 py-2 text-gray-500">{{ $price->quantity_gram }}</td>
                        <td class="px-3 py-2">
                            <input type="number" name="wholesale_prices[{{ $price->id }}][final_price]"
                                   value="{{ $price->final_price }}" step="0.01" min="0"
                                   class="border rounded px-2 py-1 w-24 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        </td>
                        <td class="px-3 py-2">
                            <input type="number" name="wholesale_prices[{{ $price->id }}][min_order_qty]"
                                   value="{{ $price->min_order_qty }}" min="1" placeholder="—"
                                   class="border rounded px-2 py-1 w-20 text-sm focus:outline-none">
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" name="wholesale_prices[{{ $price->id }}][is_active]" value="1"
                                   {{ $price->is_active ? 'checked' : '' }}>
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox" name="wholesale_prices[{{ $price->id }}][_delete]" value="1">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <p class="text-xs font-medium text-gray-600 mb-2">নতুন পাইকারি অপশন যোগ করুন:</p>
        <div id="new-wholesale-rows">
            <div class="new-ws-row grid grid-cols-5 gap-2 mb-2">
                <input type="text" name="new_wholesale_prices[0][label]" placeholder="২৫ কেজি বস্তা"
                       class="border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 col-span-2">
                <input type="number" name="new_wholesale_prices[0][quantity_gram]" placeholder="গ্রাম (25000)"
                       class="border rounded px-2 py-1 text-sm font-mono focus:outline-none">
                <input type="number" name="new_wholesale_prices[0][final_price]" placeholder="দাম ৳" step="0.01" min="0"
                       class="border rounded px-2 py-1 text-sm font-mono focus:outline-none">
                <label class="flex items-center gap-1 text-xs text-gray-600">
                    <input type="checkbox" name="new_wholesale_prices[0][is_active]" value="1" checked> সক্রিয়
                </label>
            </div>
        </div>
        <button type="button" id="add-ws-row"
                class="mt-2 text-xs text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded px-3 py-1">
            + আরো অপশন যোগ করুন
        </button>
    </div>

    {{-- Variants --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">ভেরিয়েন্ট / গ্রেড / অরিজিন</h2>
        <p class="text-xs text-gray-400 mb-4">উদাহরণ: ইরানি জিরা, ইন্ডিয়ান জিরা বা Small / Medium / Large গ্রেড।</p>

        @foreach($variants as $variant)
        <div class="border rounded-lg p-4 mb-4 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-3">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">নাম</label>
                    <input type="text" name="variants[{{ $variant->id }}][name]" value="{{ $variant->name }}"
                           class="w-full border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">অরিজিন</label>
                    <input type="text" name="variants[{{ $variant->id }}][origin]" value="{{ $variant->origin }}"
                           class="w-full border rounded px-2 py-1 text-sm focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">গ্রেড</label>
                    <input type="text" name="variants[{{ $variant->id }}][grade]" value="{{ $variant->grade }}"
                           class="w-full border rounded px-2 py-1 text-sm focus:outline-none">
                </div>
            </div>
            <div class="flex items-center gap-4 text-sm">
                <label class="flex items-center gap-1 text-xs">
                    <input type="checkbox" name="variants[{{ $variant->id }}][is_active]" value="1" {{ $variant->is_active ? 'checked' : '' }}> সক্রিয়
                </label>
                <label class="flex items-center gap-1 text-xs text-red-600">
                    <input type="checkbox" name="variants[{{ $variant->id }}][_delete]" value="1"> এই ভেরিয়েন্ট মুছুন
                </label>
            </div>

            @php
                $vRetail = $variant->prices->where('sell_type', 'retail');
                $vWholesale = $variant->prices->where('sell_type', 'wholesale');
            @endphp

            @if($vRetail->isNotEmpty())
            <div class="mt-3">
                <p class="text-xs font-medium text-gray-600 mb-1">খুচরা দাম</p>
                @foreach($vRetail as $vp)
                <div class="flex gap-2 items-center mb-1">
                    <input type="text" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][label]" value="{{ $vp->label }}"
                           class="border rounded px-2 py-1 text-xs w-32 focus:outline-none">
                    <input type="number" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][final_price]" value="{{ $vp->final_price }}" step="0.01"
                           class="border rounded px-2 py-1 text-xs w-24 font-mono focus:outline-none">
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][is_active]" value="1" {{ $vp->is_active ? 'checked' : '' }}> সক্রিয়</label>
                    <label class="flex items-center gap-1 text-xs text-red-600"><input type="checkbox" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][_delete]" value="1"> মুছুন</label>
                </div>
                @endforeach
            </div>
            @endif

            @if($vWholesale->isNotEmpty())
            <div class="mt-3">
                <p class="text-xs font-medium text-gray-600 mb-1">পাইকারি দাম</p>
                @foreach($vWholesale as $vp)
                <div class="flex gap-2 items-center mb-1">
                    <input type="text" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][label]" value="{{ $vp->label }}"
                           class="border rounded px-2 py-1 text-xs w-32 focus:outline-none">
                    <input type="number" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][final_price]" value="{{ $vp->final_price }}" step="0.01"
                           class="border rounded px-2 py-1 text-xs w-24 font-mono focus:outline-none">
                    <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][is_active]" value="1" {{ $vp->is_active ? 'checked' : '' }}> সক্রিয়</label>
                    <label class="flex items-center gap-1 text-xs text-red-600"><input type="checkbox" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][_delete]" value="1"> মুছুন</label>
                </div>
                @endforeach
            </div>
            @endif
        </div>
        @endforeach

        <details class="mt-2">
            <summary class="cursor-pointer text-sm text-indigo-600 hover:text-indigo-800">+ নতুন ভেরিয়েন্ট যোগ করুন</summary>
            <div id="new-variants-container" class="mt-3 space-y-4">
                <div class="border rounded-lg p-4 bg-indigo-50 new-variant-block">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-2">
                        <input type="text" name="new_variants[0][name]" placeholder="নাম *"
                               class="border rounded px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                        <input type="text" name="new_variants[0][origin]" placeholder="অরিজিন"
                               class="border rounded px-2 py-1 text-sm focus:outline-none">
                        <input type="text" name="new_variants[0][grade]" placeholder="গ্রেড"
                               class="border rounded px-2 py-1 text-sm focus:outline-none">
                    </div>
                    <label class="flex items-center gap-1 text-xs mb-2">
                        <input type="checkbox" name="new_variants[0][is_active]" value="1" checked> সক্রিয়
                    </label>
                    <p class="text-xs font-medium text-gray-600 mb-1">খুচরা প্যাক সাইজ:</p>
                    <div class="flex gap-2 mb-1">
                        <input type="text" name="new_variants[0][new_retail_prices][0][label]" placeholder="লেবেল"
                               class="border rounded px-2 py-1 text-xs w-32 focus:outline-none">
                        <input type="number" name="new_variants[0][new_retail_prices][0][quantity_gram]" placeholder="গ্রাম"
                               class="border rounded px-2 py-1 text-xs w-20 focus:outline-none">
                        <input type="number" name="new_variants[0][new_retail_prices][0][final_price]" placeholder="দাম ৳" step="0.01"
                               class="border rounded px-2 py-1 text-xs w-24 font-mono focus:outline-none">
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="new_variants[0][new_retail_prices][0][is_active]" value="1" checked> সক্রিয়</label>
                    </div>
                    <p class="text-xs font-medium text-gray-600 mb-1 mt-2">পাইকারি অপশন:</p>
                    <div class="flex gap-2 mb-1">
                        <input type="text" name="new_variants[0][new_wholesale_prices][0][label]" placeholder="লেবেল"
                               class="border rounded px-2 py-1 text-xs w-32 focus:outline-none">
                        <input type="number" name="new_variants[0][new_wholesale_prices][0][quantity_gram]" placeholder="গ্রাম"
                               class="border rounded px-2 py-1 text-xs w-20 focus:outline-none">
                        <input type="number" name="new_variants[0][new_wholesale_prices][0][final_price]" placeholder="দাম ৳" step="0.01"
                               class="border rounded px-2 py-1 text-xs w-24 font-mono focus:outline-none">
                        <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="new_variants[0][new_wholesale_prices][0][is_active]" value="1" checked> সক্রিয়</label>
                    </div>
                </div>
            </div>
        </details>
    </div>

    <div class="flex gap-3 items-center">
        <button type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded hover:bg-indigo-700 transition-colors text-sm font-medium">
            পণ্য আপডেট করুন
        </button>
        <a href="{{ route('vendor.products.index') }}"
           class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">
            বাতিল
        </a>
        <form method="POST" action="{{ route('vendor.products.destroy', $product) }}"
              onsubmit="return confirm('সত্যিই এই পণ্যটি মুছে ফেলবেন?')"
              class="ml-auto">
            @csrf @method('DELETE')
            <button type="submit" class="bg-red-50 text-red-600 hover:bg-red-100 px-4 py-2 rounded text-sm transition-colors">
                পণ্য মুছুন
            </button>
        </form>
    </div>
</form>

@push('scripts')
<script>
let wsRowCount = 1;
document.getElementById('add-ws-row')?.addEventListener('click', function() {
    const container = document.getElementById('new-wholesale-rows');
    const div = document.createElement('div');
    div.className = 'new-ws-row grid grid-cols-5 gap-2 mb-2';
    div.innerHTML = `
        <input type="text" name="new_wholesale_prices[${wsRowCount}][label]" placeholder="২৫ কেজি বস্তা"
               class="border rounded px-2 py-1 text-sm focus:outline-none col-span-2">
        <input type="number" name="new_wholesale_prices[${wsRowCount}][quantity_gram]" placeholder="গ্রাম"
               class="border rounded px-2 py-1 text-sm font-mono focus:outline-none">
        <input type="number" name="new_wholesale_prices[${wsRowCount}][final_price]" placeholder="দাম ৳" step="0.01"
               class="border rounded px-2 py-1 text-sm font-mono focus:outline-none">
        <label class="flex items-center gap-1 text-xs text-gray-600">
            <input type="checkbox" name="new_wholesale_prices[${wsRowCount}][is_active]" value="1" checked> সক্রিয়
        </label>`;
    container.appendChild(div);
    wsRowCount++;
});
</script>
@endpush

@endsection
