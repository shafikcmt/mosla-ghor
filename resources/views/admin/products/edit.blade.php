@extends('admin.layout')

@section('title', $product->name_bn . ' — সম্পাদনা')

@section('content')

<div class="flex items-center gap-3 mb-5">
    <a href="{{ route('admin.products.index') }}" class="text-gray-400 hover:text-gray-600 text-sm">← তালিকায় ফিরুন</a>
    <span class="text-gray-300">/</span>
    <h1 class="text-xl font-bold text-gray-800">{{ $product->name_bn }}</h1>
    @if(! $product->is_active)
        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-1 rounded-full">নিষ্ক্রিয়</span>
    @endif
</div>

<form action="{{ route('admin.products.update', $product) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    {{-- Product fields --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-4 pb-2 border-b">পণ্যের তথ্য</h2>
        @include('admin.products._form', ['product' => $product])
    </div>

    {{-- Retail pack price overrides --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">খুচরা প্যাক সাইজ ও দাম</h2>
        <p class="text-xs text-gray-400 mb-4">
            ম্যানুয়াল দাম চালু করলে সেই মানটি গ্রাহকের কাছে দেখানো হবে। অক্রিয় প্যাক স্টোরে দেখাবে না।
            ১ কেজির দাম পরিবর্তন করলে সব ম্যানুয়াল-অফ প্যাকের দাম স্বয়ংক্রিয়ভাবে আপডেট হবে।
        </p>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">প্যাক</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">স্বয়ংক্রিয় দাম</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">ম্যানুয়াল দাম (৳)</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">ম্যানুয়াল<br>চালু?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">চূড়ান্ত দাম</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($retailPrices as $price)
                    <tr class="{{ $price->is_manual_override ? 'bg-amber-50' : '' }} {{ ! $price->is_active ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2.5">
                            <span class="font-medium">{{ $price->label }}</span>
                            <span class="text-xs text-gray-400 ml-1">({{ $price->quantity_gram }}g)</span>
                        </td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono">৳{{ $price->auto_price }}</td>
                        <td class="px-3 py-2.5">
                            <input type="number"
                                   name="prices[{{ $price->id }}][manual_price]"
                                   value="{{ $price->manual_price }}"
                                   step="0.01" min="0"
                                   placeholder="—"
                                   class="border rounded px-2 py-1 w-24 text-sm focus:outline-none focus:ring-1 focus:ring-blue-400 font-mono">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="prices[{{ $price->id }}][is_manual_override]"
                                   value="1"
                                   {{ $price->is_manual_override ? 'checked' : '' }}
                                   class="w-4 h-4 rounded cursor-pointer">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="prices[{{ $price->id }}][is_active]"
                                   value="1"
                                   {{ $price->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded cursor-pointer">
                        </td>
                        <td class="px-3 py-2.5 font-semibold text-gray-800 font-mono">
                            ৳{{ $price->final_price }}
                            @if($price->is_manual_override)
                                <span class="text-xs font-normal text-amber-600 ml-1">(ম্যানুয়াল)</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Wholesale price options --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">পাইকারি অপশন</h2>
        <p class="text-xs text-gray-400 mb-4">
            পাইকারি ক্রেতাদের জন্য বড় প্যাক (যেমন: ২৫ কেজি বস্তা, ৫০ কেজি বস্তা, ১২ পিস কার্টন)।
            দাম সরাসরি নির্ধারণ করুন।
        </p>

        {{-- Existing wholesale prices --}}
        @if($wholesalePrices->isNotEmpty())
        <div class="overflow-x-auto mb-5">
            <table class="w-full text-sm">
                <thead class="bg-orange-50 border-b">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">লেবেল</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">পরিমাণ (গ্রাম)</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">দাম (৳)</th>
                        <th class="px-3 py-2 text-left font-medium text-gray-600">ন্যূনতম অর্ডার</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">সক্রিয়?</th>
                        <th class="px-3 py-2 text-center font-medium text-gray-600">মুছুন?</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($wholesalePrices as $wp)
                    <tr class="{{ ! $wp->is_active ? 'opacity-50' : '' }}">
                        <td class="px-3 py-2.5">
                            <input type="text"
                                   name="wholesale_prices[{{ $wp->id }}][label]"
                                   value="{{ $wp->label }}"
                                   class="border rounded px-2 py-1 w-36 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400">
                        </td>
                        <td class="px-3 py-2.5 text-gray-500 font-mono">{{ $wp->quantity_gram }}</td>
                        <td class="px-3 py-2.5">
                            <input type="number"
                                   name="wholesale_prices[{{ $wp->id }}][final_price]"
                                   value="{{ $wp->final_price }}"
                                   step="0.01" min="0"
                                   class="border rounded px-2 py-1 w-24 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400 font-mono">
                        </td>
                        <td class="px-3 py-2.5">
                            <input type="number"
                                   name="wholesale_prices[{{ $wp->id }}][min_order_qty]"
                                   value="{{ $wp->min_order_qty }}"
                                   min="1" placeholder="—"
                                   class="border rounded px-2 py-1 w-20 text-sm focus:outline-none focus:ring-1 focus:ring-orange-400">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="wholesale_prices[{{ $wp->id }}][is_active]"
                                   value="1"
                                   {{ $wp->is_active ? 'checked' : '' }}
                                   class="w-4 h-4 rounded cursor-pointer">
                        </td>
                        <td class="px-3 py-2.5 text-center">
                            <input type="checkbox"
                                   name="wholesale_prices[{{ $wp->id }}][_delete]"
                                   value="1"
                                   class="w-4 h-4 rounded cursor-pointer accent-red-500">
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Add new wholesale options --}}
        <div id="new-wholesale-rows">
            <p class="text-xs font-semibold text-gray-600 mb-2">নতুন পাইকারি অপশন যোগ করুন</p>
            <div id="wholesale-row-container" class="space-y-2"></div>
            <button type="button" onclick="addWholesaleRow()"
                    class="mt-2 text-xs text-orange-600 hover:text-orange-800 font-semibold underline">
                + আরেকটি অপশন যোগ করুন
            </button>
        </div>
    </div>

    {{-- Variant / Grade / Origin / Size section --}}
    <div class="bg-white shadow rounded p-6 mb-5">
        <h2 class="text-base font-semibold text-gray-700 mb-1">ভ্যারিয়েন্ট / গ্রেড / উৎস (ঐচ্ছিক)</h2>
        <p class="text-xs text-gray-400 mb-4">
            একই পণ্যের একাধিক ধরন থাকলে এখানে যোগ করুন (যেমন: ইরানি জিরা, দেশি জিরা)।
            ভ্যারিয়েন্ট থাকলে প্রতিটির নিচে আলাদাভাবে দাম সেট করুন।
            পুরনো পণ্যের জন্য এটি ঐচ্ছিক — ভ্যারিয়েন্ট ছাড়া পণ্যও সমান কার্যকর।
        </p>

        {{-- Existing variants --}}
        @foreach($variants as $variant)
        <div class="border border-purple-100 rounded-xl p-4 mb-4 bg-purple-50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="font-semibold text-purple-800 text-sm">ভ্যারিয়েন্ট: {{ $variant->name }}</h3>
                <label class="flex items-center gap-1.5 cursor-pointer text-xs">
                    <input type="checkbox" name="variants[{{ $variant->id }}][_delete]" value="1"
                           class="w-3.5 h-3.5 accent-red-500">
                    <span class="text-red-500 font-semibold">মুছুন</span>
                </label>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">নাম *</label>
                    <input type="text" name="variants[{{ $variant->id }}][name]" value="{{ $variant->name }}"
                           class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">উৎস (Origin)</label>
                    <input type="text" name="variants[{{ $variant->id }}][origin]" value="{{ $variant->origin }}"
                           placeholder="যেমন: ইরান, ভারত"
                           class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">গ্রেড</label>
                    <input type="text" name="variants[{{ $variant->id }}][grade]" value="{{ $variant->grade }}"
                           placeholder="যেমন: A, Premium"
                           class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">সাইজ লেবেল</label>
                    <input type="text" name="variants[{{ $variant->id }}][size_label]" value="{{ $variant->size_label }}"
                           placeholder="যেমন: Small, Large"
                           class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400">
                </div>
            </div>
            <div class="flex items-center gap-4 mb-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">সাজানোর ক্রম</label>
                    <input type="number" name="variants[{{ $variant->id }}][sort_order]" value="{{ $variant->sort_order }}"
                           min="0" class="border rounded px-2 py-1.5 text-xs w-20 focus:outline-none focus:ring-1 focus:ring-purple-400">
                </div>
                <label class="flex items-center gap-1.5 cursor-pointer text-xs mt-4">
                    <input type="checkbox" name="variants[{{ $variant->id }}][is_active]" value="1"
                           {{ $variant->is_active ? 'checked' : '' }}
                           class="w-3.5 h-3.5 accent-purple-600">
                    <span class="text-gray-600">সক্রিয়</span>
                </label>
            </div>

            {{-- Existing retail prices for this variant --}}
            @php $vRetailPrices = $variant->prices->where('sell_type', 'retail'); @endphp
            @if($vRetailPrices->isNotEmpty())
            <div class="mb-3">
                <p class="text-xs font-semibold text-green-700 mb-1">খুচরা দাম</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-green-50 border-b">
                            <tr>
                                <th class="px-2 py-1.5 text-left">লেবেল</th>
                                <th class="px-2 py-1.5 text-left">গ্রাম</th>
                                <th class="px-2 py-1.5 text-left">দাম (৳)</th>
                                <th class="px-2 py-1.5 text-center">সক্রিয়?</th>
                                <th class="px-2 py-1.5 text-center">মুছুন?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($vRetailPrices as $vp)
                            <tr>
                                <td class="px-2 py-1.5">
                                    <input type="text" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][label]"
                                           value="{{ $vp->label }}"
                                           class="border rounded px-2 py-1 w-28 text-xs focus:outline-none focus:ring-1 focus:ring-green-400">
                                </td>
                                <td class="px-2 py-1.5 font-mono text-gray-500">{{ $vp->quantity_gram }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="number" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][final_price]"
                                           value="{{ $vp->final_price }}" step="0.01" min="0"
                                           class="border rounded px-2 py-1 w-20 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-green-400">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input type="checkbox" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][is_active]"
                                           value="1" {{ $vp->is_active ? 'checked' : '' }}
                                           class="w-3.5 h-3.5">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input type="checkbox" name="variants[{{ $variant->id }}][retail_prices][{{ $vp->id }}][_delete]"
                                           value="1" class="w-3.5 h-3.5 accent-red-500">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- New retail prices for this existing variant --}}
            <div class="mb-3">
                <p class="text-xs font-semibold text-green-600 mb-1">নতুন খুচরা দাম যোগ</p>
                <div id="variant-retail-rows-{{ $variant->id }}" class="space-y-1.5"></div>
                <button type="button" onclick="addVariantPriceRow('retail', {{ $variant->id }})"
                        class="mt-1 text-xs text-green-600 hover:text-green-800 font-semibold underline">+ খুচরা দাম যোগ</button>
            </div>

            {{-- Existing wholesale prices for this variant --}}
            @php $vWholesalePrices = $variant->prices->where('sell_type', 'wholesale'); @endphp
            @if($vWholesalePrices->isNotEmpty())
            <div class="mb-3">
                <p class="text-xs font-semibold text-orange-700 mb-1">পাইকারি দাম</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead class="bg-orange-50 border-b">
                            <tr>
                                <th class="px-2 py-1.5 text-left">লেবেল</th>
                                <th class="px-2 py-1.5 text-left">গ্রাম</th>
                                <th class="px-2 py-1.5 text-left">দাম (৳)</th>
                                <th class="px-2 py-1.5 text-left">ন্যূ. অর্ডার</th>
                                <th class="px-2 py-1.5 text-center">সক্রিয়?</th>
                                <th class="px-2 py-1.5 text-center">মুছুন?</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($vWholesalePrices as $vp)
                            <tr>
                                <td class="px-2 py-1.5">
                                    <input type="text" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][label]"
                                           value="{{ $vp->label }}"
                                           class="border rounded px-2 py-1 w-28 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400">
                                </td>
                                <td class="px-2 py-1.5 font-mono text-gray-500">{{ $vp->quantity_gram }}</td>
                                <td class="px-2 py-1.5">
                                    <input type="number" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][final_price]"
                                           value="{{ $vp->final_price }}" step="0.01" min="0"
                                           class="border rounded px-2 py-1 w-20 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-orange-400">
                                </td>
                                <td class="px-2 py-1.5">
                                    <input type="number" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][min_order_qty]"
                                           value="{{ $vp->min_order_qty }}" min="1" placeholder="—"
                                           class="border rounded px-2 py-1 w-16 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input type="checkbox" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][is_active]"
                                           value="1" {{ $vp->is_active ? 'checked' : '' }}
                                           class="w-3.5 h-3.5">
                                </td>
                                <td class="px-2 py-1.5 text-center">
                                    <input type="checkbox" name="variants[{{ $variant->id }}][wholesale_prices][{{ $vp->id }}][_delete]"
                                           value="1" class="w-3.5 h-3.5 accent-red-500">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- New wholesale prices for this existing variant --}}
            <div>
                <p class="text-xs font-semibold text-orange-600 mb-1">নতুন পাইকারি দাম যোগ</p>
                <div id="variant-wholesale-rows-{{ $variant->id }}" class="space-y-1.5"></div>
                <button type="button" onclick="addVariantPriceRow('wholesale', {{ $variant->id }})"
                        class="mt-1 text-xs text-orange-600 hover:text-orange-800 font-semibold underline">+ পাইকারি দাম যোগ</button>
            </div>
        </div>
        @endforeach

        {{-- New variants --}}
        <div id="new-variants-container" class="space-y-4 mb-3"></div>

        <button type="button" onclick="addNewVariant()"
                class="text-xs text-purple-700 hover:text-purple-900 font-semibold underline">
            + নতুন ভ্যারিয়েন্ট যোগ করুন
        </button>
    </div>

    <div class="flex items-center justify-between">
        <div class="flex gap-3">
            <button type="submit"
                    class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 transition-colors text-sm font-medium">
                পরিবর্তন সংরক্ষণ করুন
            </button>
            <a href="{{ route('admin.products.index') }}"
               class="bg-gray-100 text-gray-600 px-5 py-2 rounded hover:bg-gray-200 transition-colors text-sm">
                বাতিল
            </a>
        </div>
    </div>

</form>

{{-- Delete form is intentionally outside the update form --}}
<form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="mt-4"
      onsubmit="return confirm('\"{{ $product->name_bn }}\" এবং এর সব প্যাক সম্পূর্ণভাবে মুছে ফেলবেন?')">
    @csrf
    @method('DELETE')
    <button type="submit"
            class="text-red-500 hover:text-red-700 text-sm underline underline-offset-2">
        পণ্য মুছুন
    </button>
</form>

<script>
let wholesaleRowIndex = 0;
let newVariantIndex   = 0;
let variantPriceIndexes = {};

function addVariantPriceRow(sellType, variantId) {
    const key = sellType + '_' + variantId;
    if (!variantPriceIndexes[key]) variantPriceIndexes[key] = 0;
    const i = variantPriceIndexes[key]++;
    const containerId = 'variant-' + sellType + '-rows-' + variantId;
    const container = document.getElementById(containerId);
    if (!container) return;
    const prefix = 'variants[' + variantId + '][new_' + sellType + '_prices][' + i + ']';
    const color = sellType === 'retail' ? 'green' : 'orange';
    const row = document.createElement('div');
    row.className = 'grid grid-cols-2 sm:grid-cols-4 gap-2 items-end p-2 bg-' + color + '-50 rounded border border-' + color + '-100';
    row.innerHTML = `
        <div><label class="block text-[10px] text-gray-500 mb-0.5">লেবেল *</label>
        <input type="text" name="${prefix}[label]" placeholder="যেমন: ১০০ গ্রাম"
               class="w-full border rounded px-2 py-1 text-xs focus:outline-none"></div>
        <div><label class="block text-[10px] text-gray-500 mb-0.5">গ্রাম *</label>
        <input type="number" name="${prefix}[quantity_gram]" placeholder="100" min="1"
               class="w-full border rounded px-2 py-1 text-xs font-mono focus:outline-none"></div>
        <div><label class="block text-[10px] text-gray-500 mb-0.5">দাম (৳) *</label>
        <input type="number" name="${prefix}[final_price]" placeholder="0.00" step="0.01" min="0"
               class="w-full border rounded px-2 py-1 text-xs font-mono focus:outline-none"></div>
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-1 text-xs cursor-pointer">
                <input type="checkbox" name="${prefix}[is_active]" value="1" checked class="w-3 h-3"> সক্রিয়
            </label>
            <button type="button" onclick="this.closest('div.grid').remove()"
                    class="text-red-400 hover:text-red-600 text-xs font-bold">✕</button>
        </div>`;
    container.appendChild(row);
}

let newVariantPriceIndexes = {};

function addNewVariantPriceRow(sellType, variantIdx) {
    const key = sellType + '_nv_' + variantIdx;
    if (!newVariantPriceIndexes[key]) newVariantPriceIndexes[key] = 0;
    const i = newVariantPriceIndexes[key]++;
    const containerId = 'new-variant-' + sellType + '-rows-' + variantIdx;
    const container = document.getElementById(containerId);
    if (!container) return;
    const prefix = 'new_variants[' + variantIdx + '][' + sellType + '_prices][' + i + ']';
    const color = sellType === 'retail' ? 'green' : 'orange';
    const row = document.createElement('div');
    row.className = 'grid grid-cols-2 sm:grid-cols-4 gap-2 items-end p-2 bg-' + color + '-50 rounded border border-' + color + '-100';
    row.innerHTML = `
        <div><label class="block text-[10px] text-gray-500 mb-0.5">লেবেল *</label>
        <input type="text" name="${prefix}[label]" placeholder="যেমন: ১০০ গ্রাম"
               class="w-full border rounded px-2 py-1 text-xs focus:outline-none"></div>
        <div><label class="block text-[10px] text-gray-500 mb-0.5">গ্রাম *</label>
        <input type="number" name="${prefix}[quantity_gram]" placeholder="100" min="1"
               class="w-full border rounded px-2 py-1 text-xs font-mono focus:outline-none"></div>
        <div><label class="block text-[10px] text-gray-500 mb-0.5">দাম (৳) *</label>
        <input type="number" name="${prefix}[final_price]" placeholder="0.00" step="0.01" min="0"
               class="w-full border rounded px-2 py-1 text-xs font-mono focus:outline-none"></div>
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-1 text-xs cursor-pointer">
                <input type="checkbox" name="${prefix}[is_active]" value="1" checked class="w-3 h-3"> সক্রিয়
            </label>
            <button type="button" onclick="this.closest('div.grid').remove()"
                    class="text-red-400 hover:text-red-600 text-xs font-bold">✕</button>
        </div>`;
    container.appendChild(row);
}

function addNewVariant() {
    const i = newVariantIndex++;
    const container = document.getElementById('new-variants-container');
    const block = document.createElement('div');
    block.className = 'border border-purple-200 rounded-xl p-4 bg-purple-50';
    block.innerHTML = `
        <div class="flex justify-between mb-3">
            <h4 class="text-sm font-semibold text-purple-700">নতুন ভ্যারিয়েন্ট</h4>
            <button type="button" onclick="this.closest('.border').remove()"
                    class="text-red-400 hover:text-red-600 text-xs font-bold">✕ বাদ দিন</button>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3">
            <div><label class="block text-xs text-gray-500 mb-1">নাম *</label>
            <input type="text" name="new_variants[${i}][name]" placeholder="যেমন: ইরানি জিরা"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400"></div>
            <div><label class="block text-xs text-gray-500 mb-1">উৎস</label>
            <input type="text" name="new_variants[${i}][origin]" placeholder="যেমন: ইরান"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400"></div>
            <div><label class="block text-xs text-gray-500 mb-1">গ্রেড</label>
            <input type="text" name="new_variants[${i}][grade]" placeholder="যেমন: Premium"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400"></div>
            <div><label class="block text-xs text-gray-500 mb-1">সাইজ লেবেল</label>
            <input type="text" name="new_variants[${i}][size_label]"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-purple-400"></div>
        </div>
        <div class="flex items-center gap-4 mb-3">
            <div><label class="block text-xs text-gray-500 mb-1">সাজানোর ক্রম</label>
            <input type="number" name="new_variants[${i}][sort_order]" value="0" min="0"
                   class="border rounded px-2 py-1.5 text-xs w-20 focus:outline-none focus:ring-1 focus:ring-purple-400"></div>
            <label class="flex items-center gap-1.5 cursor-pointer text-xs mt-4">
                <input type="checkbox" name="new_variants[${i}][is_active]" value="1" checked class="w-3.5 h-3.5 accent-purple-600"> সক্রিয়
            </label>
        </div>
        <div class="mb-3">
            <p class="text-xs font-semibold text-green-600 mb-1">খুচরা দাম</p>
            <div id="new-variant-retail-rows-${i}" class="space-y-1.5"></div>
            <button type="button" onclick="addNewVariantPriceRow('retail', ${i})"
                    class="mt-1 text-xs text-green-600 hover:text-green-800 font-semibold underline">+ খুচরা দাম যোগ</button>
        </div>
        <div>
            <p class="text-xs font-semibold text-orange-600 mb-1">পাইকারি দাম</p>
            <div id="new-variant-wholesale-rows-${i}" class="space-y-1.5"></div>
            <button type="button" onclick="addNewVariantPriceRow('wholesale', ${i})"
                    class="mt-1 text-xs text-orange-600 hover:text-orange-800 font-semibold underline">+ পাইকারি দাম যোগ</button>
        </div>`;
    container.appendChild(block);
}

function addWholesaleRow() {
    const i = wholesaleRowIndex++;
    const container = document.getElementById('wholesale-row-container');
    const row = document.createElement('div');
    row.className = 'grid grid-cols-2 sm:grid-cols-5 gap-2 items-end p-3 bg-orange-50 rounded border border-orange-100';
    row.innerHTML = `
        <div>
            <label class="block text-xs text-gray-500 mb-1">লেবেল *</label>
            <input type="text" name="new_wholesale_prices[${i}][label]"
                   placeholder="যেমন: ২৫ কেজি বস্তা"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">পরিমাণ (গ্রাম) *</label>
            <input type="number" name="new_wholesale_prices[${i}][quantity_gram]"
                   placeholder="যেমন: 25000"
                   min="1"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400 font-mono">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">দাম (৳) *</label>
            <input type="number" name="new_wholesale_prices[${i}][final_price]"
                   placeholder="0.00" step="0.01" min="0"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400 font-mono">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">ন্যূনতম অর্ডার</label>
            <input type="number" name="new_wholesale_prices[${i}][min_order_qty]"
                   placeholder="—" min="1"
                   class="w-full border rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-orange-400">
        </div>
        <div class="flex items-center gap-3">
            <label class="flex items-center gap-1 cursor-pointer text-xs">
                <input type="checkbox" name="new_wholesale_prices[${i}][is_active]" value="1" checked class="w-3.5 h-3.5">
                সক্রিয়
            </label>
            <button type="button" onclick="this.closest('div.grid').remove()"
                    class="text-red-400 hover:text-red-600 text-xs font-semibold">✕ বাদ</button>
        </div>
    `;
    container.appendChild(row);
}
</script>

@endsection
