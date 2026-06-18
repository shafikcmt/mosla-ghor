{{-- WooCommerce-style variant manager (shared by admin + vendor product forms).
     Expects $product (nullable). The enclosing <form> MUST be multipart/form-data.
     Submits:
       variants[<id>][name|sku|retail_price|sale_price|stock|is_active|_delete|remove_image]
       variants[<id>][image_file]   (file)
       new_variants[<i>][...] + new_variants[<i>][image_file]
       default_variant = "existing:<id>" | "new:<i>"
--}}
@php
    $vmProduct  = $product ?? null;
    $vmVariants = $vmProduct ? $vmProduct->variants()->orderByDesc('is_default')->orderBy('sort_order')->orderBy('id')->get() : collect();
@endphp

<div class="md:col-span-2 border border-purple-100 bg-purple-50/40 rounded-lg p-4">
    <h3 class="text-sm font-bold text-purple-800 mb-1">ভ্যারিয়েন্ট (ঐচ্ছিক)</h3>
    <p class="text-xs text-gray-400 mb-3">
        একই পণ্যের একাধিক ধরন থাকলে যোগ করুন — যেমন: ইরানি জিরা, দেশি জিরা, বড় এলাচ, ছোট এলাচ।
        শুধু নাম দিলেই হবে; দাম/স্টক/ছবি ঐচ্ছিক (খালি থাকলে মূল পণ্যের মান ব্যবহার হবে)।
    </p>

    <div id="variant-list" class="space-y-3">
        @foreach($vmVariants as $variant)
        @php $vurl = $variant->imageUrl(); @endphp
        <div class="variant-card bg-white border border-purple-100 rounded-xl p-3">
            <div class="flex items-start gap-3">
                {{-- Image --}}
                <div class="flex-shrink-0 w-16">
                    <div class="w-16 h-16 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                        <img class="vm-preview w-full h-full object-cover {{ $vurl ? '' : 'hidden' }}" src="{{ $vurl ?: '' }}" alt="">
                        <span class="vm-noimg text-[10px] text-gray-300 {{ $vurl ? 'hidden' : '' }}">ছবি নেই</span>
                    </div>
                    <label class="mt-1 block text-[10px] text-purple-600 hover:text-purple-800 cursor-pointer text-center">
                        ছবি দিন
                        <input type="file" name="variants[{{ $variant->id }}][image_file]" accept="image/jpeg,image/png,image/webp"
                               class="hidden" onchange="vmPreview(this)">
                    </label>
                    @if($vurl)
                    <label class="mt-0.5 flex items-center justify-center gap-1 text-[10px] text-red-500 cursor-pointer">
                        <input type="checkbox" name="variants[{{ $variant->id }}][remove_image]" value="1" class="w-3 h-3 accent-red-500"> মুছুন
                    </label>
                    @endif
                </div>

                {{-- Fields --}}
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="text" name="variants[{{ $variant->id }}][name]" value="{{ $variant->name }}"
                               placeholder="ভ্যারিয়েন্ট নাম *"
                               class="flex-1 min-w-[140px] border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400">
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 whitespace-nowrap">
                            <input type="radio" name="default_variant" value="existing:{{ $variant->id }}"
                                   {{ $variant->is_default ? 'checked' : '' }} class="w-4 h-4 accent-purple-600"> ডিফল্ট
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 whitespace-nowrap">
                            <input type="checkbox" name="variants[{{ $variant->id }}][is_active]" value="1"
                                   {{ $variant->is_active ? 'checked' : '' }} class="w-4 h-4 rounded accent-purple-600"> সক্রিয়
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-xs text-red-500 whitespace-nowrap">
                            <input type="checkbox" name="variants[{{ $variant->id }}][_delete]" value="1" class="w-4 h-4 rounded accent-red-500"> মুছুন
                        </label>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <input type="number" step="0.01" min="0" name="variants[{{ $variant->id }}][retail_price]" value="{{ $variant->retail_price !== null ? rtrim(rtrim(number_format((float)$variant->retail_price, 2, '.', ''), '0'), '.') : '' }}"
                               placeholder="খুচরা দাম ৳" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">
                        <input type="number" step="0.01" min="0" name="variants[{{ $variant->id }}][sale_price]" value="{{ $variant->sale_price !== null ? rtrim(rtrim(number_format((float)$variant->sale_price, 2, '.', ''), '0'), '.') : '' }}"
                               placeholder="অফার দাম ৳" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">
                        <input type="number" step="1" min="0" name="variants[{{ $variant->id }}][stock]" value="{{ $variant->stock !== null ? (int)$variant->stock : '' }}"
                               placeholder="স্টক" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">
                        <input type="text" name="variants[{{ $variant->id }}][sku]" value="{{ $variant->sku }}"
                               placeholder="SKU" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div id="new-variant-list" class="space-y-3 mt-3"></div>

    <button type="button" onclick="addProductVariant()"
            class="mt-3 inline-flex items-center gap-1 text-sm text-purple-700 hover:text-purple-900 font-semibold">
        <span class="text-lg leading-none">+</span> নতুন ভ্যারিয়েন্ট যোগ করুন
    </button>
</div>

<script>
// Live image preview for any variant file input (existing + new cards).
function vmPreview(input) {
    const card = input.closest('.variant-card');
    if (!card) return;
    const img = card.querySelector('.vm-preview');
    const noimg = card.querySelector('.vm-noimg');
    const file = input.files && input.files[0];
    if (!file || !img) return;
    const url = URL.createObjectURL(file);
    img.src = url; img.classList.remove('hidden');
    if (noimg) noimg.classList.add('hidden');
}

// Repeatable WooCommerce-style variant card.
let productVariantIndex = 0;
function addProductVariant() {
    const i = productVariantIndex++;
    const list = document.getElementById('new-variant-list');
    if (!list) return;
    const card = document.createElement('div');
    card.className = 'variant-card bg-white border border-purple-100 rounded-xl p-3';
    card.innerHTML =
        '<div class="flex items-start gap-3">' +
            '<div class="flex-shrink-0 w-16">' +
                '<div class="w-16 h-16 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">' +
                    '<img class="vm-preview w-full h-full object-cover hidden" src="" alt="">' +
                    '<span class="vm-noimg text-[10px] text-gray-300">ছবি নেই</span>' +
                '</div>' +
                '<label class="mt-1 block text-[10px] text-purple-600 hover:text-purple-800 cursor-pointer text-center">ছবি দিন' +
                    '<input type="file" name="new_variants[' + i + '][image_file]" accept="image/jpeg,image/png,image/webp" class="hidden" onchange="vmPreview(this)">' +
                '</label>' +
            '</div>' +
            '<div class="flex-1 min-w-0 space-y-2">' +
                '<div class="flex flex-wrap items-center gap-2">' +
                    '<input type="text" name="new_variants[' + i + '][name]" placeholder="ভ্যারিয়েন্ট নাম *" class="flex-1 min-w-[140px] border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-purple-400">' +
                    '<label class="flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 whitespace-nowrap"><input type="radio" name="default_variant" value="new:' + i + '" class="w-4 h-4 accent-purple-600"> ডিফল্ট</label>' +
                    '<label class="flex items-center gap-1.5 cursor-pointer text-xs text-gray-600 whitespace-nowrap"><input type="checkbox" name="new_variants[' + i + '][is_active]" value="1" checked class="w-4 h-4 rounded accent-purple-600"> সক্রিয়</label>' +
                    '<button type="button" onclick="this.closest(\'.variant-card\').remove()" class="text-red-400 hover:text-red-600 text-sm font-bold px-1">✕</button>' +
                '</div>' +
                '<div class="grid grid-cols-2 sm:grid-cols-4 gap-2">' +
                    '<input type="number" step="0.01" min="0" name="new_variants[' + i + '][retail_price]" placeholder="খুচরা দাম ৳" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">' +
                    '<input type="number" step="0.01" min="0" name="new_variants[' + i + '][sale_price]" placeholder="অফার দাম ৳" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">' +
                    '<input type="number" step="1" min="0" name="new_variants[' + i + '][stock]" placeholder="স্টক" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">' +
                    '<input type="text" name="new_variants[' + i + '][sku]" placeholder="SKU" class="border rounded px-2 py-1.5 text-xs font-mono focus:outline-none focus:ring-1 focus:ring-purple-400">' +
                '</div>' +
            '</div>' +
        '</div>';
    list.appendChild(card);
    card.querySelector('input[type="text"]').focus();
}
</script>
