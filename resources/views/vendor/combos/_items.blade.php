{{-- Combo items — only vendor's own approved products --}}
<p class="text-xs text-gray-500 mb-4">আপনার অনুমোদিত পণ্য থেকে কম্বোর আইটেম বেছে নিন। বিক্রয় ধরনের সাথে মিলে এমন প্যাক সাইজ দেখাবে।</p>

@if($products->isEmpty())
    <div class="py-6 text-center text-gray-400 text-sm border border-dashed rounded-lg">
        অনুমোদিত কোনো পণ্য নেই। প্রথমে পণ্য যোগ করুন এবং অ্যাডমিন অনুমোদনের জন্য অপেক্ষা করুন।
    </div>
@else

@php
    $currentItems = $combo?->items?->keyBy('product_id') ?? collect();
@endphp

<div class="space-y-3" id="combo-items-list">
@foreach($products as $product)

@php
    $retailPrices    = $product->activePrices->where('sell_type', 'retail')->values();
    $wholesalePrices = $product->activePrices->where('sell_type', 'wholesale')->values();
    $hasRetail       = $retailPrices->isNotEmpty();
    $hasWholesale    = $wholesalePrices->isNotEmpty();
    $currentItem     = $currentItems->get($product->id);
    $isIncluded      = (bool) $currentItem;
@endphp

<div class="border rounded-lg p-3 bg-gray-50 product-item-row"
     data-has-retail="{{ $hasRetail ? 'true' : 'false' }}"
     data-has-wholesale="{{ $hasWholesale ? 'true' : 'false' }}">
    <div class="flex items-start gap-3">
        <input type="checkbox" name="items[{{ $product->id }}][include]" value="1"
               id="item_{{ $product->id }}"
               {{ $isIncluded ? 'checked' : '' }}
               class="item-checkbox mt-1 w-4 h-4 rounded flex-shrink-0"
               data-product="{{ $product->id }}">
        <div class="flex-1">
            <label for="item_{{ $product->id }}" class="font-medium text-sm text-gray-800 cursor-pointer">
                {{ $product->name_bn }}
                @if($product->name_en)<span class="text-gray-400 font-normal text-xs">({{ $product->name_en }})</span>@endif
            </label>

            {{-- Retail options --}}
            @if($hasRetail)
            <div class="mt-2 retail-options">
                <p class="text-xs text-blue-700 font-medium mb-1">খুচরা অপশন:</p>
                <select name="items[{{ $product->id }}][price_id]"
                        class="border rounded px-2 py-1 text-xs w-full md:w-64 focus:outline-none focus:ring-1 focus:ring-indigo-400 retail-select">
                    <option value="">— প্যাক সাইজ বেছে নিন —</option>
                    @foreach($retailPrices as $price)
                    <option value="{{ $price->id }}"
                        {{ $currentItem && $currentItem->sell_type === 'retail' && $currentItem->product_price_id == $price->id ? 'selected' : '' }}>
                        {{ $price->label }} — ৳{{ $price->final_price }}
                        @if($price->variant) ({{ $price->variant->name }}) @endif
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Wholesale options --}}
            @if($hasWholesale)
            <div class="mt-2 wholesale-options">
                <p class="text-xs text-orange-700 font-medium mb-1">পাইকারি অপশন:</p>
                <select name="items[{{ $product->id }}][price_id]"
                        class="border rounded px-2 py-1 text-xs w-full md:w-64 focus:outline-none focus:ring-1 focus:ring-indigo-400 wholesale-select">
                    <option value="">— প্যাক সাইজ বেছে নিন —</option>
                    @foreach($wholesalePrices as $price)
                    <option value="{{ $price->id }}"
                        {{ $currentItem && $currentItem->sell_type === 'wholesale' && $currentItem->product_price_id == $price->id ? 'selected' : '' }}>
                        {{ $price->label }} — ৳{{ $price->final_price }}
                        @if($price->variant) ({{ $price->variant->name }}) @endif
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

        </div>
    </div>
</div>

@endforeach
</div>

<script>
(function() {
    function filterBySellType() {
        const sellTypeEl = document.querySelector('[name="sell_type"]');
        if (!sellTypeEl) return;
        const sellType = sellTypeEl.value;
        document.querySelectorAll('.product-item-row').forEach(function(row) {
            const retailOpts    = row.querySelector('.retail-options');
            const wholesaleOpts = row.querySelector('.wholesale-options');
            if (retailOpts) retailOpts.style.display    = (!sellType || sellType === 'retail') ? '' : 'none';
            if (wholesaleOpts) wholesaleOpts.style.display = (!sellType || sellType === 'wholesale') ? '' : 'none';
        });
    }

    const sellTypeEl = document.querySelector('[name="sell_type"]');
    if (sellTypeEl) {
        sellTypeEl.addEventListener('change', filterBySellType);
        filterBySellType();
    }
})();
</script>

@endif
