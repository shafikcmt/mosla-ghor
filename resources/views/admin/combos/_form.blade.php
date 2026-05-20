<div class="px-6 py-5 space-y-5">

    {{-- sell_type --}}
    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-2">বিক্রয় ধরন <span class="text-red-500">*</span></label>
        <div class="flex gap-4">
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="sell_type" value="retail"
                       {{ old('sell_type', $combo?->sell_type ?? 'retail') === 'retail' ? 'checked' : '' }}
                       onchange="onComboSellTypeChange('retail')"
                       class="w-4 h-4 accent-gray-800">
                <span class="text-sm font-medium text-gray-700">খুচরা (Retail)</span>
            </label>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="radio" name="sell_type" value="wholesale"
                       {{ old('sell_type', $combo?->sell_type ?? 'retail') === 'wholesale' ? 'checked' : '' }}
                       onchange="onComboSellTypeChange('wholesale')"
                       class="w-4 h-4 accent-orange-500">
                <span class="text-sm font-medium text-orange-700">পাইকারি (Wholesale)</span>
            </label>
        </div>
        @error('sell_type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">কম্বোর নাম <span class="text-red-500">*</span></label>
            <input type="text" name="name"
                   value="{{ old('name', $combo?->name) }}"
                   placeholder="যেমন: ট্রায়াল কম্বো"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">Slug <span class="text-red-500">*</span></label>
            <input type="text" name="slug"
                   value="{{ old('slug', $combo?->slug) }}"
                   placeholder="trial-combo"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('slug') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-xs font-semibold text-gray-600 mb-1.5">সংক্ষিপ্ত বিবরণ</label>
        <input type="text" name="short_description"
               value="{{ old('short_description', $combo?->short_description) }}"
               placeholder="যেমন: নতুন গ্রাহকদের জন্য পারফেক্ট শুরু"
               class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
        @error('short_description') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">ব্যাজ টেক্সট <span class="text-gray-400 font-normal">(ঐচ্ছিক)</span></label>
            <input type="text" name="badge_text"
                   value="{{ old('badge_text', $combo?->badge_text) }}"
                   placeholder="যেমন: জনপ্রিয়"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('badge_text') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">বিক্রয় মূল্য (৳) <span class="text-red-500">*</span></label>
            <input type="number" name="sell_price" min="1" step="1"
                   value="{{ old('sell_price', $combo?->sell_price) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
            @error('sell_price') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1.5">সাজানোর ক্রম</label>
            <input type="number" name="sort_order" min="0" step="1"
                   value="{{ old('sort_order', $combo?->sort_order ?? 0) }}"
                   class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-gray-400">
        </div>
    </div>

    <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="is_active" value="1"
               {{ old('is_active', $combo ? ($combo->is_active ? '1' : '') : '1') ? 'checked' : '' }}
               class="w-4 h-4 accent-gray-800">
        <div>
            <div class="text-sm font-semibold text-gray-700">কম্বো সক্রিয়</div>
            <div class="text-xs text-gray-400">নিষ্ক্রিয় কম্বো ফ্রন্টেন্ডে দেখাবে না</div>
        </div>
    </label>

</div>

{{-- Product Items --}}
<div class="border-t border-gray-100 px-6 py-5">
    <h3 class="text-sm font-semibold text-gray-700 mb-1">কম্বোর পণ্য সমূহ</h3>
    <p class="text-xs text-gray-400 mb-1">টিক দিন এবং পরিমাণ বেছে নিন। মূল্য স্বয়ংক্রিয়ভাবে product_prices থেকে নেওয়া হবে।</p>
    <p id="combo-selltype-note" class="text-xs text-orange-500 mb-4 hidden">পাইকারি মোড: শুধু পাইকারি অপশন দেখাচ্ছে।</p>

    @if($products->isEmpty())
        <p class="text-gray-400 text-sm">কোনো সক্রিয় পণ্য নেই।</p>
    @else
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-gray-200">
                    <th class="pb-2 text-left font-semibold text-gray-500 text-xs uppercase">অন্তর্ভুক্ত</th>
                    <th class="pb-2 text-left font-semibold text-gray-500 text-xs uppercase">পণ্য</th>
                    <th class="pb-2 text-left font-semibold text-gray-500 text-xs uppercase">পরিমাণ ও মূল্য</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50" id="combo-product-rows">
                @foreach($products as $product)
                @php
                    $saved          = $currentItems[$product->id] ?? null;
                    $retailPrices   = $product->activePrices->where('sell_type', 'retail');
                    $wholesalePrices = $product->activePrices->where('sell_type', 'wholesale');
                @endphp
                <tr class="hover:bg-gray-50" data-product-row="{{ $product->id }}">
                    <td class="py-2.5 pr-4 w-10">
                        <input type="checkbox" name="items[{{ $product->id }}][include]" value="1"
                               {{ $saved ? 'checked' : '' }}
                               class="w-4 h-4 accent-gray-800">
                    </td>
                    <td class="py-2.5 pr-6">
                        <div class="font-medium text-gray-800">{{ $product->name_bn }}</div>
                        @if($product->name_en) <div class="text-xs text-gray-400">{{ $product->name_en }}</div> @endif
                    </td>
                    <td class="py-2.5">
                        @php
                            $hasPrices = $product->activePrices->isNotEmpty();
                        @endphp
                        @if(! $hasPrices)
                            <span class="text-gray-400 text-xs">কোনো মূল্য নেই</span>
                        @else
                        <select name="items[{{ $product->id }}][price_id]"
                                data-product-price-select="{{ $product->id }}"
                                class="border border-gray-300 rounded px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-gray-400 bg-white">
                            @foreach($product->activePrices as $price)
                            <option value="{{ $price->id }}"
                                    data-sell-type="{{ $price->sell_type }}"
                                    {{ $saved && (int)$saved->product_price_id === $price->id ? 'selected' : '' }}>
                                [{{ $price->sell_type === 'retail' ? 'খুচরা' : 'পাইকারি' }}]{{ $price->variant ? ' [' . $price->variant->name . ']' : '' }} {{ $price->label }} — ৳{{ number_format($price->final_price, 0) }}
                            </option>
                            @endforeach
                        </select>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<script>
function onComboSellTypeChange(sellType) {
    const note = document.getElementById('combo-selltype-note');
    if (note) note.classList.toggle('hidden', sellType !== 'wholesale');

    document.querySelectorAll('[data-product-price-select]').forEach(function(sel) {
        const opts = Array.from(sel.options);
        const hasSellType = opts.some(o => o.dataset.sellType === sellType);

        // Show/hide row based on whether product has options for this sell_type
        const row = sel.closest('tr');
        if (row) row.style.display = hasSellType ? '' : 'none';

        // Filter visible options and set first matching as selected
        let firstMatch = null;
        opts.forEach(function(opt) {
            const match = opt.dataset.sellType === sellType;
            opt.disabled = !match;
            opt.style.display = match ? '' : 'none';
            if (match && !firstMatch) firstMatch = opt;
        });

        if (firstMatch && !opts.find(o => o.selected && o.dataset.sellType === sellType)) {
            sel.value = firstMatch.value;
        }
    });
}

// Init on page load
(function() {
    const checked = document.querySelector('input[name="sell_type"]:checked');
    if (checked) onComboSellTypeChange(checked.value);
})();
</script>
