@php $product = $product ?? null; @endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-5">

    {{-- category --}}
    @include('partials.category-select', [
        'categories' => $categories ?? [],
        'selected'   => $product?->category_id,
        'ring'       => 'focus:ring-indigo-400',
    ])

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">বাংলা নাম <span class="text-red-500">*</span></label>
        <input type="text" name="name_bn" id="name_bn" value="{{ old('name_bn', $product?->name_bn) }}" required autofocus
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('name_bn') border-red-400 @enderror">
        @error('name_bn')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    {{-- slug: advanced/optional, auto-generated from name --}}
    <div class="md:col-span-2">
        <details {{ $errors->has('slug') ? 'open' : '' }} class="border border-gray-200 rounded bg-gray-50/60">
            <summary class="cursor-pointer select-none px-3 py-2 text-xs font-medium text-gray-500 hover:text-gray-700">
                উন্নত বিকল্প (Slug/URL)
            </summary>
            <div class="px-3 pb-3 pt-1">
                <div class="flex gap-2">
                    <input type="text" name="slug" id="slug" value="{{ old('slug', $product?->slug) }}"
                           class="flex-1 border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('slug') border-red-400 @enderror">
                    <button type="button" id="btn-gen-slug"
                            class="text-xs bg-gray-100 hover:bg-gray-200 border px-3 py-2 rounded text-gray-600 whitespace-nowrap">Auto</button>
                </div>
                <p class="text-xs text-gray-400 mt-1">নাম থেকে স্বয়ংক্রিয়ভাবে তৈরি হয়। সাধারণত পরিবর্তনের দরকার নেই।</p>
                @error('slug')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
        </details>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">খুচরা দাম — ১ কেজি (৳) <span class="text-red-500">*</span></label>
        <input type="number" name="retail_price_1kg" value="{{ old('retail_price_1kg', $product?->retail_price_1kg) }}" step="0.01" min="0.01" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 @error('retail_price_1kg') border-red-400 @enderror">
        <p class="text-xs text-gray-400 mt-1">সব প্যাকের দাম এই মূল্য থেকে স্বয়ংক্রিয়ভাবে হিসাব হবে।</p>
        @error('retail_price_1kg')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">স্টক (কেজি) <span class="text-red-500">*</span></label>
        <input type="number" name="stock" value="{{ old('stock', $product?->stock ?? 0) }}" min="0" required
               class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
        <p class="text-xs text-gray-400 mt-1">ওজনভিত্তিক (মসলা) পণ্যের প্রধান স্টক।</p>
    </div>

    {{-- ── Inventory / stock metadata (optional) ──────────────────────────── --}}
    <div class="md:col-span-2 mt-1 pt-4 border-t border-gray-100">
        <p class="text-xs font-bold uppercase tracking-wide text-indigo-500 mb-3">ইনভেন্টরি তথ্য (ঐচ্ছিক)</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                <input type="text" name="sku" value="{{ old('sku', $product?->sku) }}"
                       class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ক্যাটাগরি</label>
                <input type="text" name="category" value="{{ old('category', $product?->category) }}"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ব্র্যান্ড</label>
                <input type="text" name="brand" value="{{ old('brand', $product?->brand) }}"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">একক (Unit)</label>
                @php $curUnit = old('unit', $product?->unit ?? 'kg'); @endphp
                <select name="unit" class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-indigo-400">
                    @foreach(\App\Models\Product::UNITS as $u)
                        <option value="{{ $u }}" {{ $curUnit === $u ? 'selected' : '' }}>{{ $u }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ক্রয় মূল্য (৳)</label>
                <input type="number" name="purchase_price" value="{{ old('purchase_price', $product?->purchase_price) }}" step="0.01" min="0"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">বিক্রয় মূল্য (৳)</label>
                <input type="number" name="selling_price" value="{{ old('selling_price', $product?->selling_price) }}" step="0.01" min="0"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                <p class="text-xs text-gray-400 mt-1">POS অর্ডারে ডিফল্ট দাম।</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">লো-স্টক সীমা</label>
                <input type="number" name="low_stock_threshold" value="{{ old('low_stock_threshold', $product?->low_stock_threshold ?? 0) }}" step="0.001" min="0"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">
                <p class="text-xs text-gray-400 mt-1">এর নিচে নামলে অ্যালার্ট।</p>
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3 pt-5">
        <input type="checkbox" name="is_active" id="is_active" value="1"
               {{ old('is_active', $product?->is_active ?? true) ? 'checked' : '' }}
               class="w-4 h-4 rounded cursor-pointer">
        <label for="is_active" class="text-sm font-medium text-gray-700 cursor-pointer">
            পণ্যটি সক্রিয় <span class="text-gray-400 font-normal">(স্টোরে দেখাবে)</span>
        </label>
    </div>

    {{-- ── পাইকারি / Wholesale settings ──────────────────────────────────── --}}
    <div class="md:col-span-2 border border-amber-200 bg-amber-50/50 rounded-lg p-4 space-y-4">
        <h3 class="text-sm font-bold text-amber-800">পাইকারি / Wholesale সেটিংস</h3>

        <label class="flex items-start gap-3 cursor-pointer">
            <input type="checkbox" name="is_wholesale" id="is_wholesale" value="1"
                   {{ old('is_wholesale', $product?->is_wholesale ?? false) ? 'checked' : '' }}
                   class="w-4 h-4 rounded cursor-pointer mt-0.5">
            <span class="text-sm text-gray-700">
                এই পণ্যটি পাইকারি (Paykari)
                <span class="block text-xs text-gray-400 font-normal">দাম পাবলিক পেজে লুকানো থাকবে — গ্রাহক শুধু enquiry পাঠাবে, আপনি/admin quote দেবেন।</span>
            </span>
        </label>

        <label class="flex items-center gap-3 cursor-pointer">
            <input type="checkbox" name="wholesale_enquiry_enabled" id="wholesale_enquiry_enabled" value="1"
                   {{ old('wholesale_enquiry_enabled', $product?->wholesale_enquiry_enabled ?? true) ? 'checked' : '' }}
                   class="w-4 h-4 rounded cursor-pointer">
            <span class="text-sm text-gray-700">পাইকারি enquiry চালু (Enquiry form দেখাবে)</span>
        </label>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">সর্বনিম্ন অর্ডার পরিমাণ (MOQ)</label>
                <div class="flex gap-2">
                    <input type="number" name="min_order_quantity" step="0.01" min="0"
                           value="{{ old('min_order_quantity', $product?->min_order_quantity) }}"
                           placeholder="যেমন: 50"
                           class="flex-1 border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
                    <select name="min_order_unit"
                            class="border rounded px-3 py-2 text-sm bg-white focus:outline-none focus:ring-1 focus:ring-amber-400">
                        @foreach(['kg','gram','bag','carton','piece','packet'] as $u)
                            <option value="{{ $u }}" {{ old('min_order_unit', $product?->min_order_unit ?? 'kg') === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">ডেলিভারি সময়</label>
                <input type="text" name="delivery_time"
                       value="{{ old('delivery_time', $product?->delivery_time) }}"
                       placeholder="যেমন: ৩-৫ দিন"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">পেমেন্ট শর্ত (নমুনা)</label>
                <input type="text" name="payment_terms"
                       value="{{ old('payment_terms', $product?->payment_terms) }}"
                       placeholder="যেমন: ৩০% অগ্রিম, বাকি ডেলিভারিতে"
                       class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-amber-400">
            </div>
        </div>
    </div>

    {{-- main image --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">মূল ছবি আপলোড</label>
        @if($product?->main_image && str_starts_with($product->main_image, 'storage/'))
        <div class="mb-3 flex items-start gap-3 p-3 bg-gray-50 border rounded">
            <img src="{{ asset($product->main_image) }}" alt="" class="w-24 h-20 object-cover rounded border flex-shrink-0">
            <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer mt-2">
                <input type="checkbox" name="remove_main_image" value="1" class="rounded">
                বর্তমান ছবি মুছুন
            </label>
        </div>
        @endif
        <input type="file" name="main_image_file" accept="image/jpeg,image/png,image/webp"
               class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">JPG, PNG, WebP — সর্বোচ্চ ৫ MB</p>
    </div>

    {{-- gallery --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">গ্যালারি ছবি (একাধিক)</label>
        @php $galleryImages = $product?->gallery_images ?? []; @endphp
        @if(count($galleryImages))
        <div class="mb-3 flex flex-wrap gap-2 p-3 bg-gray-50 border rounded">
            @foreach($galleryImages as $gi)
            <div class="flex flex-col items-center gap-1">
                <img src="{{ str_starts_with($gi, 'http') ? $gi : asset($gi) }}" alt="" class="w-20 h-16 object-cover rounded border">
                <label class="flex items-center gap-1 text-xs text-red-600 cursor-pointer">
                    <input type="checkbox" name="remove_gallery[]" value="{{ $gi }}" class="rounded"> মুছুন
                </label>
            </div>
            @endforeach
        </div>
        @endif
        <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple
               class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">একাধিক ছবি বেছে নিন — প্রতিটি সর্বোচ্চ ৫ MB</p>
    </div>

    {{-- video url --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ভিডিও URL (YouTube / বাহ্যিক)</label>
        <input type="text" name="video_url" value="{{ old('video_url', $product?->video_url) }}"
               placeholder="https://www.youtube.com/watch?v=..."
               class="w-full border rounded px-3 py-2 text-sm font-mono focus:outline-none focus:ring-1 focus:ring-indigo-400">
    </div>

    {{-- video file --}}
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">ভিডিও আপলোড (লোকাল)</label>
        @if(($product?->video_path ?? null) && str_starts_with($product->video_path, 'storage/'))
        <div class="mb-3 p-3 bg-gray-50 border rounded">
            <video src="{{ asset($product->video_path) }}" class="w-full max-w-xs h-28 rounded border bg-black" controls></video>
            <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer mt-1">
                <input type="checkbox" name="remove_video" value="1" class="rounded"> বর্তমান ভিডিও মুছুন
            </label>
        </div>
        @endif
        <input type="file" name="video_file" accept="video/mp4,video/webm,video/quicktime"
               class="w-full border rounded px-3 py-2 text-sm bg-white focus:outline-none">
        <p class="text-xs text-gray-400 mt-1">MP4, WebM, MOV — সর্বোচ্চ ৫০ MB</p>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">সংক্ষিপ্ত বিবরণ</label>
        <textarea name="short_description" rows="2"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400 resize-none">{{ old('short_description', $product?->short_description) }}</textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">বিস্তারিত বিবরণ</label>
        <textarea name="description" rows="5"
                  class="w-full border rounded px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-indigo-400">{{ old('description', $product?->description) }}</textarea>
    </div>

    {{-- ── ভ্যারিয়েন্ট (WooCommerce-style, ঐচ্ছিক) ─────────────────────────── --}}
    @include('partials.admin.variant-manager', ['product' => $product])

</div>

<script>
(function () {
    const nameBn = document.getElementById('name_bn');
    const slugEl = document.getElementById('slug');
    const btnGen = document.getElementById('btn-gen-slug');

    // Minimal Bangla → latin map so a Bangla-only name still yields a usable slug.
    const bnMap = {
        'অ':'o','আ':'a','ই':'i','ঈ':'i','উ':'u','ঊ':'u','ঋ':'ri','এ':'e','ঐ':'oi','ও':'o','ঔ':'ou',
        'ক':'k','খ':'kh','গ':'g','ঘ':'gh','ঙ':'ng','চ':'ch','ছ':'chh','জ':'j','ঝ':'jh','ঞ':'n',
        'ট':'t','ঠ':'th','ড':'d','ঢ':'dh','ণ':'n','ত':'t','থ':'th','দ':'d','ধ':'dh','ন':'n',
        'প':'p','ফ':'ph','ব':'b','ভ':'bh','ম':'m','য':'j','র':'r','ল':'l',
        'শ':'sh','ষ':'sh','স':'s','হ':'h','ড়':'r','ঢ়':'rh','য়':'y','ৎ':'t','ং':'ng','ঃ':'h','ঁ':'',
        'া':'a','ি':'i','ী':'i','ু':'u','ূ':'u','ৃ':'ri','ে':'e','ৈ':'oi','ো':'o','ৌ':'ou','্':'',
        '০':'0','১':'1','২':'2','৩':'3','৪':'4','৫':'5','৬':'6','৭':'7','৮':'8','৯':'9'
    };
    function translit(str) {
        let out = '';
        for (const ch of (str || '')) out += (bnMap[ch] !== undefined ? bnMap[ch] : ch);
        return out;
    }
    function toSlug(str) {
        return (str || '').toLowerCase().replace(/[^\w\s-]/g,'').trim().replace(/[\s_]+/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
    }
    // Build the slug from a transliterated Bangla name.
    function genSlug() {
        let s = toSlug(translit(nameBn ? nameBn.value : ''));
        if (!s && nameBn && nameBn.value.trim() !== '') s = 'product-' + Date.now();
        return s;
    }
    function autofill() {
        if (slugEl && !slugEl.dataset.edited) slugEl.value = genSlug();
    }
    if (nameBn) nameBn.addEventListener('input', autofill);
    if (slugEl) slugEl.addEventListener('input', function () { this.dataset.edited = '1'; });
    if (btnGen && slugEl) {
        btnGen.addEventListener('click', function () {
            slugEl.value = genSlug();
            slugEl.dataset.edited = '';
            const d = slugEl.closest('details'); if (d) d.open = true; // reveal the generated slug
        });
    }
})();
</script>
